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
            // Compteur total des queries
            Prometheus::addCounter('database_queries_total')
                ->label('connection', $query->connectionName)
                ->increment();

            // Histogram pour la durée des queries
            Prometheus::addHistogram('database_query_duration_seconds')
                ->label('connection', $query->connectionName)
                ->buckets([0.001, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5])
                ->observe($query->time / 1000); // Convertir ms en secondes

            // Compteur spécifique pour les queries lentes
            if ($query->time >= $this->slowQueryThreshold) {
                // Extraire le type de query (SELECT, INSERT, UPDATE, DELETE, etc.)
                $queryType = strtoupper(strtok(trim($query->sql), ' '));

                Prometheus::addCounter('database_slow_queries_total')
                    ->label('connection', $query->connectionName)
                    ->label('type', $queryType)
                    ->increment();

                // Gauge pour la dernière query lente (utile pour debug)
                Prometheus::addGauge('database_slow_query_duration_seconds')
                    ->label('connection', $query->connectionName)
                    ->label('type', $queryType)
                    ->set($query->time / 1000);
            }
        });
    }
}
