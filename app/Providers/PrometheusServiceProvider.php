<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;

class PrometheusServiceProvider extends ServiceProvider
{
    /**
     * Seuil en millisecondes pour considérer une query comme lente
     */
    protected int $slowQueryThreshold = 100;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerSlowQueryListener();
    }

    protected function registerSlowQueryListener(): void
    {
        DB::listen(function (QueryExecuted $query) {
            try {
                $registry = app(CollectorRegistry::class);
                $connection = $query->connectionName;
                $durationSeconds = $query->time / 1000;

                // Compteur total des queries
                $counter = $registry->getOrRegisterCounter(
                    'app',
                    'database_queries_total',
                    'Total database queries',
                    ['connection']
                );
                $counter->incBy(1, [$connection]);

                // Gauge pour la durée de la dernière query
                $gauge = $registry->getOrRegisterGauge(
                    'app',
                    'database_query_duration_seconds',
                    'Database query duration in seconds',
                    ['connection']
                );
                $gauge->set($durationSeconds, [$connection]);

                // Compteur spécifique pour les queries lentes
                if ($query->time >= $this->slowQueryThreshold) {
                    $queryType = strtoupper(strtok(trim($query->sql), ' '));

                    // Log détaillé pour Loki
                    Log::channel('loki')->warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time_ms' => $query->time,
                        'connection' => $connection,
                        'type' => $queryType,
                    ]);

                    $slowCounter = $registry->getOrRegisterCounter(
                        'app',
                        'database_slow_queries_total',
                        'Total slow database queries',
                        ['connection', 'type']
                    );
                    $slowCounter->incBy(1, [$connection, $queryType]);

                    // Gauge pour la dernière query lente
                    $slowGauge = $registry->getOrRegisterGauge(
                        'app',
                        'database_slow_query_duration_seconds',
                        'Slow database query duration in seconds',
                        ['connection', 'type']
                    );
                    $slowGauge->set($durationSeconds, [$connection, $queryType]);
                }
            } catch (\Throwable $e) {
                Log::error('PrometheusServiceProvider error: ' . $e->getMessage());
            }
        });
    }
}
