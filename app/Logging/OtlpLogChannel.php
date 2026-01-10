<?php

namespace App\Logging;

use Monolog\Logger;
use OpenTelemetry\API\Logs\LoggerProviderInterface;

/**
 * Custom log channel factory for OTLP log export.
 *
 * This class is used by Laravel's logging system to create a Monolog logger
 * that exports logs via OpenTelemetry to an OTLP endpoint.
 */
class OtlpLogChannel
{
    public function __invoke(array $config): Logger
    {
        $loggerProvider = app(LoggerProviderInterface::class);

        $handler = new OtlpHandler(
            $loggerProvider,
            $config['level'] ?? 'debug',
        );

        // Add the trace context processor to enrich logs with trace/span IDs
        $handler->pushProcessor(new TraceContextProcessor());

        return new Logger('otlp', [$handler]);
    }
}
