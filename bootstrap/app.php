<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Prometheus\CollectorRegistry;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\TracingMiddleware::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\PrometheusMiddleware::class,
        ]);

        // Trust all proxies for HTTPS detection
        $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PREFIX);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (\Throwable $e) {
            try {
                // Prometheus counter
                $registry = app(CollectorRegistry::class);
                $shortClass = class_basename($e);
                $code = (string) $e->getCode();

                $counter = $registry->getOrRegisterCounter(
                    'app',
                    'exceptions_total',
                    'Total exceptions',
                    ['exception', 'code']
                );
                $counter->incBy(1, [$shortClass, $code]);

                // Log détaillé vers Loki
                \Illuminate\Support\Facades\Log::channel('loki')->error('Exception occurred', [
                    'exception' => $shortClass,
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'url' => request()?->fullUrl(),
                    'method' => request()?->method(),
                    'user_id' => auth()->id(),
                    'user_agent' => request()?->userAgent(),
                    'ip' => request()?->ip(),
                ]);

                // Span OpenTelemetry pour l'erreur
                if (config('telemetry.enabled')) {
                    $tracer = app(\OpenTelemetry\API\Trace\TracerInterface::class);
                    $span = $tracer->spanBuilder("Exception: {$shortClass}")
                        ->setSpanKind(\OpenTelemetry\API\Trace\SpanKind::KIND_INTERNAL)
                        ->startSpan();
                    $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, $e->getMessage());
                    $span->recordException($e);
                    $span->end();
                }
            } catch (\Throwable $ignored) {
                // Ignore errors in exception reporting
            }
        });
    })->create();
