<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Prometheus\Facades\Prometheus;
use Symfony\Component\HttpFoundation\Response;

class PrometheusMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $startTime;

        $this->recordMetrics($request, $response, $duration);

        return $response;
    }

    protected function recordMetrics(Request $request, Response $response, float $duration): void
    {
        $route = $request->route()?->getName() ?? $request->route()?->uri() ?? 'unknown';
        $method = $request->method();
        $status = $response->getStatusCode();
        $statusGroup = (string) (intval($status / 100) * 100); // 200, 400, 500, etc.

        // Compteur de requêtes par route/method/status
        Prometheus::addCounter('http_requests_total')
            ->label('route', $route)
            ->label('method', $method)
            ->label('status', (string) $status)
            ->label('status_group', $statusGroup)
            ->increment();

        // Histogram pour la latence
        Prometheus::addHistogram('http_request_duration_seconds')
            ->label('route', $route)
            ->label('method', $method)
            ->buckets([0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10])
            ->observe($duration);
    }
}
