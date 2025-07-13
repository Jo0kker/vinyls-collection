<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Models\Vinyl;
use App\Mail\VinylImportCompleted;

class MonitorVinylImportProgress implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    private $importSessionId;
    private $totalDispatched;
    private $startTime;
    private $adminEmail;

    public function __construct($importSessionId, $totalDispatched, $startTime, $adminEmail)
    {
        $this->importSessionId = $importSessionId;
        $this->totalDispatched = $totalDispatched;
        $this->startTime = $startTime;
        $this->adminEmail = $adminEmail;
    }

    public function handle(): void
    {
        // Attendre que tous les jobs soient traités
        $this->waitForCompletion();

        // Calculer les statistiques finales
        $stats = $this->calculateFinalStats();

        // Nettoyer le cache
        Cache::forget("vinyl_import_session_{$this->importSessionId}");

        // Envoyer l'email de completion
        Mail::to($this->adminEmail)->send(new VinylImportCompleted(
            $this->totalDispatched,
            $stats['processed'],
            $stats['uploaded'],
            $this->startTime,
            now(),
            $stats['errors']
        ));
    }

    private function waitForCompletion()
    {
        $maxWaitTime = 7200; // 2 heures max
        $startWait = time();
        $lastCheck = 0;

        while ((time() - $startWait) < $maxWaitTime) {
            $pending = DB::table('jobs')
                ->where('queue', 'default')
                ->where('payload', 'like', '%ProcessVinylImageUpload%')
                ->count();

            $processing = DB::table('jobs')
                ->where('queue', 'default') 
                ->where('reserved_at', '!=', null)
                ->where('payload', 'like', '%ProcessVinylImageUpload%')
                ->count();

            // Si plus de jobs pending/processing depuis 5 minutes
            if ($pending === 0 && $processing === 0) {
                if ($lastCheck === 0) {
                    $lastCheck = time();
                } elseif ((time() - $lastCheck) >= 300) { // 5 minutes
                    break; // Tous les jobs sont terminés
                }
            } else {
                $lastCheck = 0; // Reset le timer
            }

            sleep(30); // Vérifier toutes les 30 secondes
        }
    }

    private function calculateFinalStats(): array
    {
        // Récupérer les stats depuis le cache si disponibles
        $processed = Cache::get("vinyl_import_processed_{$this->importSessionId}", 0);
        $uploaded = Cache::get("vinyl_import_uploaded_{$this->importSessionId}", 0);
        $errors = Cache::get("vinyl_import_errors_{$this->importSessionId}", []);

        // Si pas de stats en cache, essayer de les deviner
        if ($processed === 0) {
            $processed = $this->totalDispatched;
            $uploaded = Vinyl::whereNotNull('pochette')
                ->where('updated_at', '>=', $this->startTime)
                ->count();
        }

        return [
            'processed' => $processed,
            'uploaded' => $uploaded,
            'errors' => $errors
        ];
    }
}
