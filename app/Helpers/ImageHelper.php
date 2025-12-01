<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImageHelper
{
    /**
     * Validate URL for SSRF protection
     *
     * @param string $url The URL to validate
     * @return bool True if safe, false otherwise
     */
    private static function isSafeUrl(string $url): bool
    {
        // Parse the URL
        $parsedUrl = parse_url($url);

        if (!$parsedUrl || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
            return false;
        }

        // Only allow http and https
        if (!in_array(strtolower($parsedUrl['scheme']), ['http', 'https'])) {
            return false;
        }

        // Get the IP address
        $ip = gethostbyname($parsedUrl['host']);

        // If gethostbyname fails, it returns the hostname unchanged
        if ($ip === $parsedUrl['host'] && !filter_var($ip, FILTER_VALIDATE_IP)) {
            // Could not resolve, might be safe but let's be cautious
            return true; // Allow external domains
        }

        // Block private IP ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            Log::warning('SSRF attempt detected', ['url' => $url, 'ip' => $ip]);
            return false;
        }

        return true;
    }

    /**
     * Delete a vinyl image from S3 storage
     *
     * @param string|null $imageUrl The full URL of the image
     * @return bool True if deleted, false otherwise
     */
    public static function deleteVinylImage(?string $imageUrl): bool
    {
        if (!$imageUrl) {
            return false;
        }

        try {
            // Extraire le chemin depuis l'URL
            $path = parse_url($imageUrl, PHP_URL_PATH);
            if (!$path) {
                return false;
            }

            // Nettoyer le path (enlever le / initial et le nom du bucket si présent)
            $path = ltrim($path, '/');

            // Si le path contient le nom du bucket, on l'enlève
            // Exemple: "bucket-name/vinyls/xxx.jpg" -> "vinyls/xxx.jpg"
            $bucketName = env('AWS_BUCKET');
            if ($bucketName && str_starts_with($path, $bucketName . '/')) {
                $path = substr($path, strlen($bucketName) + 1);
            }

            // Vérifier si le fichier existe sur notre disque S3 avant de supprimer
            if (Storage::disk('s3')->exists($path)) {
                Storage::disk('s3')->delete($path);
                Log::info('Image supprimée de S3', ['path' => $path, 'url' => $imageUrl]);
                return true;
            } else {
                // L'URL ne pointe pas vers notre stockage S3 (probablement une URL externe Discogs)
                Log::info('Image non supprimée (URL externe ou fichier inexistant)', ['path' => $path, 'url' => $imageUrl]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de l\'image du vinyle: ' . $e->getMessage(), [
                'url' => $imageUrl
            ]);
            return false;
        }
    }

    /**
     * Upload vinyl image and return the URL
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string The URL of the uploaded image
     * @throws \Exception If upload fails
     */
    public static function uploadVinylImage($file): string
    {
        try {
            // Compression et redimensionnement de l'image
            $compressedImage = self::compressImage($file);

            // Générer un nom unique
            $imagePath = "vinyls/" . Str::uuid() . ".jpg";

            // Upload vers S3 (ou le disque configuré)
            $uploaded = Storage::disk('s3')->put($imagePath, $compressedImage, [
                'ContentType' => 'image/jpeg',
                'CacheControl' => 'max-age=31536000',
                'visibility' => 'public',
            ]);

            if ($uploaded) {
                return Storage::disk('s3')->url($imagePath);
            }
        } catch (\Exception $e) {
            throw new \Exception('Upload failed: ' . $e->getMessage());
        }

        throw new \Exception('Upload failed: uploaded returned false');
    }

    /**
     * Compress and resize image
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $quality Quality of compression (0-100)
     * @return string The compressed image data
     */
    public static function compressImage($file, int $quality = 80): string
    {
        try {
            $image = imagecreatefromstring($file->getContent());
            if (!$image) {
                return $file->getContent();
            }

            // Obtenir les dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Redimensionner si trop grande (max 1200x1200 pour garder une bonne qualité)
            $maxSize = 1200;
            if ($width > $maxSize || $height > $maxSize) {
                $ratio = min($maxSize / $width, $maxSize / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);

                $resized = imagecreatetruecolor($newWidth, $newHeight);

                // Améliorer la qualité du redimensionnement
                imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                imagedestroy($image);
                $image = $resized;
            }

            ob_start();
            imagejpeg($image, null, $quality);
            $compressedData = ob_get_contents();
            ob_end_clean();

            imagedestroy($image);

            // Vérifier que la compression est bénéfique
            $originalSize = strlen($file->getContent());
            $compressedSize = strlen($compressedData);

            // Si la compression augmente la taille de plus de 10%, garder l'original
            if ($compressedSize > $originalSize * 1.1) {
                return $file->getContent();
            }

            return $compressedData;

        } catch (\Exception $e) {
            return $file->getContent(); // Retourne l'original en cas d'erreur
        }
    }

    /**
     * Duplicate a vinyl image to create a new copy
     *
     * @param string $originalUrl The URL of the original image
     * @return string|null The URL of the duplicated image, or null if failed
     */
    public static function duplicateVinylImage(string $originalUrl): ?string
    {
        try {
            // Si c'est une URL externe (Discogs ou autre), télécharger l'image
            if (filter_var($originalUrl, FILTER_VALIDATE_URL)) {
                // SSRF protection: validate URL before fetching
                if (!self::isSafeUrl($originalUrl)) {
                    Log::warning('Unsafe URL blocked in duplicateVinylImage', ['url' => $originalUrl]);
                    return null;
                }

                $imageContent = @file_get_contents($originalUrl);
                if (!$imageContent) {
                    return null;
                }

                // Créer un fichier temporaire
                $tempPath = tempnam(sys_get_temp_dir(), 'vinyl_');

                try {
                    file_put_contents($tempPath, $imageContent);

                    // Créer un UploadedFile à partir du contenu
                    $file = new \Illuminate\Http\UploadedFile($tempPath, 'image.jpg', 'image/jpeg', null, true);

                    // Utiliser la méthode existante pour uploader
                    $newUrl = self::uploadVinylImage($file);

                    return $newUrl;
                } finally {
                    // Nettoyer le fichier temporaire (garanti même en cas d'exception)
                    @unlink($tempPath);
                }
            }

            // Si c'est déjà une image stockée sur S3, la dupliquer directement
            $path = parse_url($originalUrl, PHP_URL_PATH);
            if ($path) {
                $path = ltrim($path, '/');

                // Si le path contient le nom du bucket, on l'enlève
                $bucketName = env('AWS_BUCKET');
                if ($bucketName && str_starts_with($path, $bucketName . '/')) {
                    $path = substr($path, strlen($bucketName) + 1);
                }

                // Vérifier si le fichier existe sur notre disque S3
                if (Storage::disk('s3')->exists($path)) {
                    // Copier le fichier sur S3
                    $newPath = "vinyls/" . Str::uuid() . ".jpg";
                    Storage::disk('s3')->copy($path, $newPath);

                    return Storage::disk('s3')->url($newPath);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la duplication de l\'image du vinyle: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Check if an image URL is accessible
     *
     * @param string|null $imageUrl The URL of the image to check
     * @return bool True if accessible, false otherwise
     */
    public static function checkImageAccessibility(?string $imageUrl): bool
    {
        if (!$imageUrl) {
            return false; // Pas d'image = inaccessible
        }

        // Si c'est un placeholder, c'est inaccessible
        if (str_contains($imageUrl, 'placeholder')) {
            return false;
        }

        try {
            // Vérifier si l'URL répond
            $response = Http::timeout(5)->head($imageUrl);
            return $response->successful() && str_starts_with($response->header('content-type') ?? '', 'image/');
        } catch (\Exception $e) {
            Log::info('Image inaccessible détectée', ['url' => $imageUrl, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
