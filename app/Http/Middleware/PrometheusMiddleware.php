<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpFoundation\Response;

class PrometheusMiddleware
{
    public function __construct(
        protected CollectorRegistry $registry
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $startTime;

        try {
            $this->recordMetrics($request, $response, $duration);
        } catch (\Throwable $e) {
            Log::error('PrometheusMiddleware error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return $response;
    }

    protected function recordMetrics(Request $request, Response $response, float $duration): void
    {
        $route = $request->route()?->getName() ?? $request->route()?->uri() ?? 'unknown';
        $method = $request->method();
        $status = (string) $response->getStatusCode();
        $statusGroup = (string) (intval($response->getStatusCode() / 100) * 100);

        // Compteur de requêtes par route/method/status
        $counter = $this->registry->getOrRegisterCounter(
            'app',
            'http_requests_total',
            'Total HTTP requests',
            ['route', 'method', 'status', 'status_group']
        );
        $counter->incBy(1, [$route, $method, $status, $statusGroup]);

        // Gauge pour la latence de la dernière requête par route
        $gauge = $this->registry->getOrRegisterGauge(
            'app',
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['route', 'method']
        );
        $gauge->set($duration, [$route, $method]);
    }
}
