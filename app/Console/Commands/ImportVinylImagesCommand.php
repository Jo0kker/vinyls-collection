<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Vinyl;
use App\Jobs\ProcessVinylImageUpload;
use App\Jobs\MonitorVinylImportProgress;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class ImportVinylImagesCommand extends Command
{
    protected $signature = 'import:vinyl-images {--concurrent=10} {--batch=300} {--method=concurrent} {--direct-s3=true} {--queue=false} {--email=}';
    protected $description = 'Import optimisé des images depuis les colonnes visuels/pochette';

    private $defaultImageBase64 = null;
    private $s3Client = null;
    private $s3Bucket = null;

    public function handle()
    {
        $this->info('🖼️  Début de l\'import optimisé des images de vinyles...');

        // Initialiser l'image par défaut
        $this->initializeDefaultImage();

        // Initialiser le client S3 direct si demandé
        if ($this->option('direct-s3')) {
            $this->initializeS3Client();
        }

        // Compter les vinyles à traiter
        $totalVinyls = Vinyl::whereNull('pochette')->whereNotNull('visuels')->count();
        $this->info("📊 Trouvé {$totalVinyls} vinyles sans pochette à traiter");

        if ($totalVinyls === 0) {
            $this->info('✅ Aucun vinyle à traiter.');
            return 0;
        }

        // Choisir la méthode de traitement
        if ($this->option('queue')) {
            $this->processWithQueue();
        } else {
            $method = $this->option('method');
            if ($method === 'concurrent' || $this->confirm('Utiliser le mode concurrent (plus rapide) ?', true)) {
                $this->processConcurrent();
            } else {
                $this->processSequential();
            }
        }

        return 0;
    }

    private function initializeDefaultImage()
    {
        $defaultImagePath = storage_path('app/public/default_vinyl.jpg');
        if (file_exists($defaultImagePath)) {
            $this->defaultImageBase64 = base64_encode(file_get_contents($defaultImagePath));
            $this->info('✅ Image par défaut chargée');
        } else {
            $this->warn('⚠️  Image par défaut default_vinyl.jpg non trouvée');
            $this->defaultImageBase64 = '';
        }
    }

    private function initializeS3Client()
    {
        try {
            $this->s3Client = new S3Client([
                'version' => 'latest',
                'region' => env('AWS_DEFAULT_REGION'),
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);
            $this->s3Bucket = env('AWS_BUCKET');
            $this->info('✅ Client S3 direct initialisé');
        } catch (\Exception $e) {
            $this->warn('⚠️  Impossible d\'initialiser le client S3 direct, fallback vers Storage');
            $this->s3Client = null;
        }
    }

    private function determineImageUrl($vinyl)
    {
        // Utiliser directement la colonne visuels du vinyle importé
        if (!empty($vinyl->visuels) && strpos($vinyl->visuels, 'none.jpg') === false) {
            $visuels = explode(';', $vinyl->visuels);
            $firstVisual = trim($visuels[0]);

            // Vérifier que ce n'est pas juste un ; vide ou none.jpg
            if (!empty($firstVisual) && strpos($firstVisual, 'none.jpg') === false) {
                return "https://vinyls-collection.com/images/vinyls/{$firstVisual}";
            }
        }

        return null;
    }

    private function processConcurrent()
    {
        $concurrency = (int) $this->option('concurrent');
        $batchSize = (int) $this->option('batch');

        $this->info("🚀 Traitement concurrent avec {$concurrency} connexions simultanées par batch de {$batchSize}...");

        $totalVinyls = Vinyl::whereNull('pochette')->whereNotNull('visuels')->count();
        $bar = $this->output->createProgressBar($totalVinyls);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% écoulé | %estimated:-6s% estimé | %memory:6s% | %message%');
        $bar->start();

        $totalProcessed = 0;
        $totalUploaded = 0;
        $chunkNumber = 0;

        Vinyl::whereNull('pochette')->whereNotNull('visuels')->chunk($batchSize, function ($vinyls) use ($concurrency, $bar, &$totalProcessed, &$totalUploaded, &$chunkNumber) {
            $chunkNumber++;
            $result = $this->processBatchConcurrent($vinyls, $concurrency, $bar);
            $totalProcessed += $result['processed'];
            $totalUploaded += $result['uploaded'];

            // Afficher des stats régulièrement
            if ($chunkNumber % 10 === 0) {
                $successRate = $totalProcessed > 0 ? ($totalUploaded / $totalProcessed) * 100 : 0;
                $bar->setMessage(sprintf("Lot %d | Succès: %.1f%% | Images: %d/%d", $chunkNumber, $successRate, $totalUploaded, $totalProcessed));
            }

            // Pause réduite ou conditionnelle
            if (memory_get_usage() > 128 * 1024 * 1024) { // Si > 128MB
                usleep(100000); // 0.1 seconde seulement
            }
        });

        $bar->finish();
        $this->line('');
        $this->displayResults($totalProcessed, $totalUploaded);
    }

    private function processSequential()
    {
        $this->info("🐌 Traitement séquentiel (mode stable)...");

        $totalVinyls = Vinyl::whereNull('pochette')->whereNotNull('visuels')->count();
        $bar = $this->output->createProgressBar($totalVinyls);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% écoulé | %estimated:-6s% estimé | %memory:6s% | %message%');
        $bar->start();

        $processed = 0;
        $uploaded = 0;

        Vinyl::whereNull('pochette')->whereNotNull('visuels')->chunk(100, function ($vinyls) use ($bar, &$processed, &$uploaded) {
            foreach ($vinyls as $vinyl) {
                if ($this->processVinylImageSequential($vinyl)) {
                    $uploaded++;
                }
                $processed++;
                $bar->advance();

                // Pause pour éviter de surcharger
                usleep(100000); // 0.1 seconde
            }
        });

        $bar->finish();
        $this->line('');
        $this->displayResults($processed, $uploaded);
    }

    private function processBatchConcurrent($vinyls, $concurrency, $bar)
    {
        $multiHandle = curl_multi_init();
        curl_multi_setopt($multiHandle, CURLMOPT_MAX_TOTAL_CONNECTIONS, $concurrency);

        $curlHandles = [];
        $processed = 0;
        $uploaded = 0;

        // Préparer les vinyles avec leurs URLs d'images
        $vinylsWithUrls = [];
        foreach ($vinyls as $vinyl) {
            $imageUrl = $this->determineImageUrl($vinyl);
            if ($imageUrl) {
                $vinylsWithUrls[] = ['vinyl' => $vinyl, 'url' => $imageUrl];
            } else {
                // Pas d'image trouvée pour ce vinyle
                $processed++;
                $bar->advance();
            }
        }

        // Configurer les requêtes cURL
        foreach ($vinylsWithUrls as $data) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $data['url'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; VinylImporter/1.0)',
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            ]);

            curl_multi_add_handle($multiHandle, $ch);
            $curlHandles[] = ['handle' => $ch, 'vinyl' => $data['vinyl']];
        }

        // Exécuter toutes les requêtes en parallèle
        $running = null;
        do {
            $status = curl_multi_exec($multiHandle, $running);
            if ($running) {
                curl_multi_select($multiHandle, 0.1); // Timeout court pour la réactivité
            }
        } while ($running > 0 && $status === CURLM_OK);

        // Traiter les résultats
        foreach ($curlHandles as $handleData) {
            $ch = $handleData['handle'];
            $vinyl = $handleData['vinyl'];

            $imageData = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            if ($httpCode === 200 && $imageData && empty($error) && strlen($imageData) > 1000) {
                if ($this->processAndUploadImage($vinyl, $imageData)) {
                    $uploaded++;
                }
            }

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
            $processed++;
            $bar->advance();
        }

        curl_multi_close($multiHandle);

        return ['processed' => $processed, 'uploaded' => $uploaded];
    }

    private function processVinylImageSequential($vinyl)
    {
        $imageUrl = $this->determineImageUrl($vinyl);
        if (!$imageUrl) {
            return false;
        }

        try {
            $response = Http::timeout(10)->get($imageUrl);

            if ($response->successful()) {
                $imageData = $response->body();

                // Vérifier la taille minimum
                if (strlen($imageData) < 1000) {
                    return false;
                }

                return $this->processAndUploadImage($vinyl, $imageData);
            }
        } catch (\Exception $e) {
            // Échec silencieux
        }

        return false;
    }

    private function processAndUploadImage($vinyl, $imageData)
    {
        // Vérifier si c'est l'image par défaut
        if ($this->defaultImageBase64) {
            $imageBase64 = base64_encode($imageData);
            if ($imageBase64 === $this->defaultImageBase64) {
                return false; // Skip l'image par défaut
            }
        }

        try {
            // Compresser l'image
            $compressedImage = $this->compressImage($imageData);
            $imagePath = "vinyls/" . Str::uuid() . ".jpg";

            // Upload direct S3 ou fallback Storage
            if ($this->s3Client && $this->s3Bucket) {
                $uploaded = $this->uploadToS3Direct($imagePath, $compressedImage);
            } else {
                $uploaded = $this->uploadToS3Storage($imagePath, $compressedImage);
            }

            if ($uploaded) {
                $url = $this->generateS3Url($imagePath);
                $vinyl->update(['pochette' => $url]);
                return true;
            }
        } catch (\Exception $e) {
            // Échec silencieux pour éviter de polluer les logs
        }

        return false;
    }

    private function uploadToS3Direct($imagePath, $compressedImage)
    {
        try {
            $result = $this->s3Client->putObject([
                'Bucket' => $this->s3Bucket,
                'Key' => $imagePath,
                'Body' => $compressedImage,
                'ACL' => 'public-read',
                'ContentType' => 'image/jpeg',
                'CacheControl' => 'max-age=31536000',
                'Metadata' => [
                    'upload-method' => 'direct-s3'
                ]
            ]);
            return !empty($result['ObjectURL']) || !empty($result['@metadata']);
        } catch (AwsException $e) {
            return false;
        }
    }

    private function uploadToS3Storage($imagePath, $compressedImage)
    {
        return Storage::disk('s3')->put($imagePath, $compressedImage, [
            'ContentType' => 'image/jpeg',
            'CacheControl' => 'max-age=31536000',
            'visibility' => 'public',
        ]);
    }

    private function generateS3Url($imagePath)
    {
        if ($this->s3Client && $this->s3Bucket) {
            $region = env('AWS_DEFAULT_REGION');
            return "https://{$this->s3Bucket}.s3.{$region}.amazonaws.com/{$imagePath}";
        }
        return Storage::disk('s3')->url($imagePath);
    }

    private function compressImage($imageData, $quality = 80)
    {
        try {
            $image = imagecreatefromstring($imageData);
            if (!$image) {
                return $imageData; // Retourne l'original si compression échoue
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
            $originalSize = strlen($imageData);
            $compressedSize = strlen($compressedData);

            // Si la compression augmente la taille de plus de 10%, garder l'original
            if ($compressedSize > $originalSize * 1.1) {
                return $imageData;
            }

            return $compressedData;

        } catch (\Exception $e) {
            return $imageData; // Retourne l'original en cas d'erreur
        }
    }

    private function processWithQueue()
    {
        $batchSize = (int) $this->option('batch');
        $email = $this->option('email');
        
        $this->info("🚀 Traitement avec queue Laravel (ultra-rapide)...");

        $totalVinyls = Vinyl::whereNull('pochette')->whereNotNull('visuels')->count();
        $this->info("📤 Dispatch de {$totalVinyls} jobs vers la queue...");

        $bar = $this->output->createProgressBar($totalVinyls);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% écoulé | %estimated:-6s% estimé | %memory:6s% | Jobs dispatched');
        $bar->start();

        $dispatched = 0;
        $chunkCount = 0;
        $startTime = now();
        $sessionId = Str::uuid();

        Vinyl::whereNull('pochette')->whereNotNull('visuels')->chunk($batchSize, function ($vinyls) use ($bar, &$dispatched, &$chunkCount) {
            $chunkCount++;
            
            foreach ($vinyls as $vinyl) {
                $imageUrl = $this->determineImageUrl($vinyl);
                if ($imageUrl) {
                    // Délai croissant pour éviter la surcharge
                    $delay = now()->addSeconds($chunkCount * 2);
                    ProcessVinylImageUpload::dispatch($vinyl, $imageUrl, $this->defaultImageBase64)->delay($delay);
                    $dispatched++;
                }
                $bar->advance();
            }
            
            // Petite pause pour éviter de spam la queue
            if ($chunkCount % 10 === 0) {
                usleep(100000); // 0.1s pause tous les 10 chunks
            }
        });

        $bar->finish();
        $this->line('');
        $this->info("✅ {$dispatched} jobs dispatchés avec succès !");
        $this->info("⏰ Jobs étalés sur " . ceil($chunkCount * 2 / 60) . " minutes pour éviter la surcharge");
        
        // Démarrer le monitoring si email fourni
        if ($email) {
            $delay = now()->addMinutes(ceil($chunkCount * 2 / 60) + 10); // Attendre que tous les jobs soient dispatchés + 10min
            MonitorVinylImportProgress::dispatch($sessionId, $dispatched, $startTime, $email)->delay($delay);
            $this->info("📧 Email de résultat sera envoyé à: {$email}");
        }
        
        $this->info("💡 Lancez plusieurs workers: './vendor/bin/sail artisan queue:work --queue=default --sleep=1 --tries=2'");
        $this->info("📊 Surveillez: './vendor/bin/sail artisan queue:monitor'");
        
        if (!$email) {
            $this->warn("💡 Ajoutez --email=votre@email.com pour recevoir un rapport détaillé par email !");
        }
    }

    private function displayResults($processed, $uploaded)
    {
        $this->info("✅ Traitement terminé !");
        $this->info("📊 Statistiques :");
        $this->info("   • Images traitées : {$processed}");
        $this->info("   • Images uploadées : {$uploaded}");

        if ($processed > 0) {
            $successRate = round(($uploaded / $processed) * 100, 1);
            $this->info("   • Taux de réussite : {$successRate}%");

            if ($successRate < 80) {
                $this->warn("⚠️  Taux de réussite faible. Vérifiez la connectivité ou les URLs d'images.");
            } else {
                $this->info("🎉 Excellent taux de réussite !");
            }

            $remaining = Vinyl::whereNull('pochette')->count();
            if ($remaining > 0) {
                $this->warn("⚠️  Il reste {$remaining} vinyles sans pochette.");
                $this->info("💡 Vous pouvez relancer la commande pour traiter les images manquantes.");
            } else {
                $this->info("🎯 Tous les vinyles ont maintenant une pochette !");
            }
        }
    }
}
