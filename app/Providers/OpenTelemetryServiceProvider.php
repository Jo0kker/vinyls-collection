<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\ResourceAttributes;
use OpenTelemetry\SemConv\TraceAttributes;

class OpenTelemetryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TracerProviderInterface::class, function () {
            $endpoint = config('telemetry.endpoint', 'http://localhost:4318/v1/traces');

            if (empty($endpoint) || !config('telemetry.enabled', false)) {
                return null;
            }

            $transport = (new OtlpHttpTransportFactory())->create(
                $endpoint,
                'application/json'
            );

            $exporter = new SpanExporter($transport);

            $resource = ResourceInfo::create(Attributes::create([
                ResourceAttributes::SERVICE_NAME => config('app.name', 'laravel'),
                ResourceAttributes::SERVICE_VERSION => '1.0.0',
                ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => config('app.env', 'production'),
            ]));

            return TracerProvider::builder()
                ->setResource($resource)
                ->addSpanProcessor(new SimpleSpanProcessor($exporter))
                ->setSampler(new AlwaysOnSampler())
                ->build();
        });

        $this->app->singleton(TracerInterface::class, function ($app) {
            $provider = $app->make(TracerProviderInterface::class);

            if ($provider === null) {
                return Globals::tracerProvider()->getTracer('noop');
            }

            return $provider->getTracer('vinyls-collection');
        });
    }

    public function boot(): void
    {
        $this->registerDatabaseTracing();
    }

    protected function registerDatabaseTracing(): void
    {
        if (!config('telemetry.enabled') || !config('telemetry.trace_sql')) {
            return;
        }

        DB::listen(function (QueryExecuted $query) {
            $tracer = app(TracerInterface::class);

            $queryType = strtoupper(strtok(trim($query->sql), ' '));
            $durationSeconds = $query->time / 1000;

            $span = $tracer->spanBuilder("DB {$queryType}")
                ->setSpanKind(SpanKind::KIND_CLIENT)
                ->startSpan();

            $span->setAttributes([
                TraceAttributes::DB_SYSTEM_NAME => 'postgresql',
                TraceAttributes::DB_NAMESPACE => $query->connectionName,
                TraceAttributes::DB_QUERY_TEXT => $query->sql,
                TraceAttributes::DB_OPERATION_NAME => $queryType,
                'db.bindings' => json_encode($query->bindings),
                'db.duration_ms' => $query->time,
            ]);

            if ($query->time >= 100) {
                $span->setStatus(StatusCode::STATUS_OK, 'Slow query');
                $span->setAttribute('db.slow_query', true);
            } else {
                $span->setStatus(StatusCode::STATUS_OK);
            }

            $span->end();
        });
    }
}
