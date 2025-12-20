<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\SemConv\TraceAttributes;
use Symfony\Component\HttpFoundation\Response;

class TracingMiddleware
{
    public function __construct(
        protected TracerInterface $tracer
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!config('telemetry.enabled') || !config('telemetry.trace_http')) {
            return $next($request);
        }

        $route = $request->route()?->getName() ?? $request->route()?->uri() ?? 'unknown';
        $method = $request->method();

        $span = $this->tracer->spanBuilder("{$method} {$route}")
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();

        $scope = $span->activate();

        try {
            $response = $next($request);

            $span->setAttributes([
                TraceAttributes::HTTP_REQUEST_METHOD => $method,
                TraceAttributes::URL_PATH => $request->path(),
                TraceAttributes::URL_FULL => $request->fullUrl(),
                TraceAttributes::HTTP_RESPONSE_STATUS_CODE => $response->getStatusCode(),
                TraceAttributes::HTTP_ROUTE => $route,
                TraceAttributes::USER_AGENT_ORIGINAL => $request->userAgent() ?? '',
                TraceAttributes::CLIENT_ADDRESS => $request->ip() ?? '',
                'user.id' => $request->user()?->id,
                'user.email' => $request->user()?->email,
            ]);

            if ($response->getStatusCode() >= 400) {
                $span->setStatus(StatusCode::STATUS_ERROR);
            } else {
                $span->setStatus(StatusCode::STATUS_OK);
            }

            return $response;
        } catch (\Throwable $e) {
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            $span->recordException($e);
            throw $e;
        } finally {
            $span->end();
            $scope->detach();
        }
    }
}
