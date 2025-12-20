<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Spatie\Prometheus\Facades\Prometheus;

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
            $connection = $query->connectionName;
            $durationSeconds = $query->time / 1000;

            // Compteur total des queries
            Prometheus::addCounter('database_queries_total')
                ->labels(['connection'])
                ->inc(1, [$connection]);

            // Gauge pour la durée de la dernière query
            Prometheus::addGauge('database_query_duration_seconds')
                ->labels(['connection'])
                ->value($durationSeconds, [$connection]);

            // Compteur spécifique pour les queries lentes
            if ($query->time >= $this->slowQueryThreshold) {
                $queryType = strtoupper(strtok(trim($query->sql), ' '));

                Prometheus::addCounter('database_slow_queries_total')
                    ->labels(['connection', 'type'])
                    ->inc(1, [$connection, $queryType]);

                // Gauge pour la dernière query lente
                Prometheus::addGauge('database_slow_query_duration_seconds')
                    ->labels(['connection', 'type'])
                    ->value($durationSeconds, [$connection, $queryType]);
            }
        });
    }
}
