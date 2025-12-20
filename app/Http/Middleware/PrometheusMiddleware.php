<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Prometheus\Facades\Prometheus;
use Symfony\Component\HttpFoundation\Response;

class PrometheusMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::warning('PrometheusMiddleware called', ['uri' => $request->getRequestUri()]);

        $startTime = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $startTime;

        try {
            $this->recordMetrics($request, $response, $duration);
            Log::warning('PrometheusMiddleware metrics recorded');
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
        Prometheus::addCounter('http_requests_total')
            ->labels(['route', 'method', 'status', 'status_group'])
            ->inc(1, [$route, $method, $status, $statusGroup]);

        // Gauge pour la latence de la dernière requête par route
        Prometheus::addGauge('http_request_duration_seconds')
            ->labels(['route', 'method'])
            ->value($duration, [$route, $method]);
    }
}
