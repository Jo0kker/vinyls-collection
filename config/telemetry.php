<?php

return [
    /*
     * Enable or disable OpenTelemetry tracing
     */
    'enabled' => env('OTEL_ENABLED', false),

    /*
     * OpenTelemetry collector endpoint
     */
    'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318/v1/traces'),

    /*
     * Trace all SQL queries (can be verbose)
     */
    'trace_sql' => env('OTEL_TRACE_SQL', true),

    /*
     * Trace HTTP requests
     */
    'trace_http' => env('OTEL_TRACE_HTTP', true),
];
