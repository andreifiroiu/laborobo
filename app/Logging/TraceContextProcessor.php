<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use OpenTelemetry\API\Trace\Span;

/**
 * Monolog processor that adds OpenTelemetry trace context to log records.
 *
 * This enables log correlation in observability backends by including
 * trace_id and span_id in each log entry.
 */
class TraceContextProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $span = Span::getCurrent();
        $context = $span->getContext();

        if (! $context->isValid()) {
            return $record;
        }

        return $record->with(
            extra: array_merge($record->extra, [
                'trace_id' => $context->getTraceId(),
                'span_id' => $context->getSpanId(),
                'trace_flags' => $context->getTraceFlags(),
            ])
        );
    }
}
