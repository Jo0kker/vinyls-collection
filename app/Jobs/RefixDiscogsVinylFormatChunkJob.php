<?php

namespace App\Jobs;

use App\Exceptions\DiscogsRateLimitException;
use App\Models\Vinyl;
use App\Models\VinylFormat;
use App\Services\DiscogsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class RefixDiscogsVinylFormatChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 3;
    public int $backoff = 120;

    private const PROGRESS_KEY = 'vinyls:refix-discogs:progress';
    private const RATE_KEY = 'discogs-api';
    private const RATE_MAX_PER_MIN = 50;

    public function __construct(
        public int $startId = 0,
        public int $chunkSize = 50,
        public int $sleepMs = 2500,
        public bool $all = false,
    ) {}

    public function handle(DiscogsService $discogs): void
    {
        $query = Vinyl::query()
            ->whereNotNull('discogs_id')
            ->where('id', '>', $this->startId);

        if (! $this->all) {
            $query->where('vinyl_format', VinylFormat::QUARANTE_CINQ_T);
        }

        $vinyls = $query->orderBy('id')->limit($this->chunkSize)->get();

        if ($vinyls->isEmpty()) {
            Cache::forever(self::PROGRESS_KEY, [
                'status' => 'completed',
                'last_id' => $this->startId,
                'finished_at' => now()->toIso8601String(),
            ]);
            Log::info('RefixDiscogsVinylFormatChunkJob: tous les vinyles ont été traités', [
                'last_id' => $this->startId,
            ]);
            return;
        }

        $stats = ['updated' => 0, 'unchanged' => 0, 'not_found' => 0, 'failed' => 0];
        $lastId = $this->startId;

        foreach ($vinyls as $vinyl) {
            $this->throttle();

            try {
                $result = $this->processOne($vinyl, $discogs);
            } catch (DiscogsRateLimitException $e) {
                $this->updateProgress($lastId, $stats, 'rate_limited');
                Log::warning('RefixDiscogsVinylFormatChunkJob: rate limit hit, pause 70s', [
                    'last_id' => $lastId,
                    'next_vinyl' => $vinyl->id,
                ]);
                self::dispatch($lastId, $this->chunkSize, $this->sleepMs, $this->all)
                    ->delay(now()->addSeconds(70));
                return;
            }

            $stats[$result]++;
            $lastId = $vinyl->id;

            if ($this->sleepMs > 0) {
                usleep($this->sleepMs * 1000);
            }
        }

        $this->updateProgress($lastId, $stats);

        self::dispatch($lastId, $this->chunkSize, $this->sleepMs, $this->all)
            ->delay(now()->addSeconds(2));
    }

    private function throttle(): void
    {
        while (RateLimiter::tooManyAttempts(self::RATE_KEY, self::RATE_MAX_PER_MIN)) {
            $wait = max(1, RateLimiter::availableIn(self::RATE_KEY));
            sleep($wait);
        }
        RateLimiter::hit(self::RATE_KEY, 60);
    }

    private function processOne(Vinyl $vinyl, DiscogsService $discogs): string
    {
        try {
            $data = $vinyl->discogs_type === 'master'
                ? $discogs->getMaster($vinyl->discogs_id)
                : $discogs->getRelease($vinyl->discogs_id);

            if (! $data) {
                Log::warning('RefixDiscogsVinylFormatChunkJob: release introuvable', [
                    'vinyl_id' => $vinyl->id,
                    'discogs_id' => $vinyl->discogs_id,
                    'discogs_type' => $vinyl->discogs_type,
                    'http_status' => $discogs->getLastStatusCode(),
                ]);
                return 'not_found';
            }

            $newFormat = VinylFormat::fromDiscogsFormats($data['formats'] ?? null);

            if ($newFormat === (int) $vinyl->vinyl_format) {
                return 'unchanged';
            }

            $vinyl->forceFill(['vinyl_format' => $newFormat])->saveQuietly();
            return 'updated';
        } catch (DiscogsRateLimitException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('RefixDiscogsVinylFormatChunkJob: exception', [
                'vinyl_id' => $vinyl->id,
                'error' => $e->getMessage(),
            ]);
            return 'failed';
        }
    }

    private function updateProgress(int $lastId, array $stats, string $status = 'running'): void
    {
        $previous = Cache::get(self::PROGRESS_KEY, [
            'updated' => 0, 'unchanged' => 0, 'not_found' => 0, 'failed' => 0,
        ]);

        Cache::forever(self::PROGRESS_KEY, [
            'status' => $status,
            'last_id' => $lastId,
            'updated' => ($previous['updated'] ?? 0) + $stats['updated'],
            'unchanged' => ($previous['unchanged'] ?? 0) + $stats['unchanged'],
            'not_found' => ($previous['not_found'] ?? 0) + $stats['not_found'],
            'failed' => ($previous['failed'] ?? 0) + $stats['failed'],
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
