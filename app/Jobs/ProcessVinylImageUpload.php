<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Vinyl;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class ProcessVinylImageUpload implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 300; // 5 minutes max
    public $tries = 2; // Moins de retry pour éviter les blocages
    public $backoff = [30]; // Retry après 30s seulement
    public $maxExceptions = 3; // Fail définitif après 3 exceptions

    private $vinyl;
    private $imageUrl;
    private $defaultImageBase64;

    public function __construct(Vinyl $vinyl, string $imageUrl, ?string $defaultImageBase64 = null)
    {
        $this->vinyl = $vinyl;
        $this->imageUrl = $imageUrl;
        $this->defaultImageBase64 = $defaultImageBase64;
    }

    public function handle(): void
    {
        // Timeout court pour éviter les blocages
        set_time_limit(180); // 3 minutes max pour ce job
        
        try {
            // Timeout réduit pour le download
            $response = Http::timeout(15)->connectTimeout(5)->get($this->imageUrl);

            if (!$response->successful() || strlen($response->body()) < 1000) {
                return; // Fail silencieux, pas de retry
            }

            $imageData = $response->body();

            if ($this->isDefaultImage($imageData)) {
                return;
            }

            $compressedImage = $this->compressImage($imageData);
            $imagePath = "vinyls/" . Str::uuid() . ".jpg";

            if ($this->uploadToS3($imagePath, $compressedImage)) {
                $url = $this->generateS3Url($imagePath);
                $this->vinyl->update(['pochette' => $url]);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Timeout ou connection: pas de retry
            return;
        } catch (\Exception $e) {
            // Autres erreurs: retry si pas encore max attempts
            if ($this->attempts() >= $this->tries) {
                return; // Fail silencieux au lieu de fail()
            }
            throw $e;
        }
    }

    private function isDefaultImage($imageData): bool
    {
        if (!$this->defaultImageBase64) {
            return false;
        }

        return base64_encode($imageData) === $this->defaultImageBase64;
    }

    private function compressImage($imageData, $quality = 80)
    {
        try {
            $image = imagecreatefromstring($imageData);
            if (!$image) {
                return $imageData;
            }

            $width = imagesx($image);
            $height = imagesy($image);

            $maxSize = 1200;
            if ($width > $maxSize || $height > $maxSize) {
                $ratio = min($maxSize / $width, $maxSize / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);

                $resized = imagecreatetruecolor($newWidth, $newHeight);
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

            $originalSize = strlen($imageData);
            $compressedSize = strlen($compressedData);

            if ($compressedSize > $originalSize * 1.1) {
                return $imageData;
            }

            return $compressedData;

        } catch (\Exception $e) {
            return $imageData;
        }
    }

    private function uploadToS3($imagePath, $compressedImage): bool
    {
        try {
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => env('AWS_DEFAULT_REGION'),
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);

            $result = $s3Client->putObject([
                'Bucket' => env('AWS_BUCKET'),
                'Key' => $imagePath,
                'Body' => $compressedImage,
                'ACL' => 'public-read',
                'ContentType' => 'image/jpeg',
                'CacheControl' => 'max-age=31536000',
                'Metadata' => [
                    'upload-method' => 'queue-job'
                ]
            ]);

            return !empty($result['ObjectURL']) || !empty($result['@metadata']);

        } catch (AwsException $e) {
            return Storage::disk('s3')->put($imagePath, $compressedImage, [
                'ContentType' => 'image/jpeg',
                'CacheControl' => 'max-age=31536000',
                'visibility' => 'public',
            ]);
        }
    }

    private function generateS3Url($imagePath): string
    {
        $region = env('AWS_DEFAULT_REGION');
        $bucket = env('AWS_BUCKET');
        return "https://{$bucket}.s3.{$region}.amazonaws.com/{$imagePath}";
    }
}
