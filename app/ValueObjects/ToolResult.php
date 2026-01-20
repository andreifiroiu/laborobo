<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Immutable value object representing the result of a tool execution.
 */
final readonly class ToolResult
{
    private function __construct(
        public bool $success,
        public mixed $data,
        public ?string $error,
        public int $executionTimeMs,
        public string $status,
    ) {}

    /**
     * Create a successful result.
     *
     * @param  mixed  $data  The result data from the tool execution
     * @param  int  $executionTimeMs  The execution time in milliseconds
     */
    public static function success(mixed $data, int $executionTimeMs = 0): self
    {
        return new self(
            success: true,
            data: $data,
            error: null,
            executionTimeMs: $executionTimeMs,
            status: 'success',
        );
    }

    /**
     * Create a failure result.
     *
     * @param  string  $error  The error message
     * @param  int  $executionTimeMs  The execution time in milliseconds
     */
    public static function failure(string $error, int $executionTimeMs = 0): self
    {
        return new self(
            success: false,
            data: null,
            error: $error,
            executionTimeMs: $executionTimeMs,
            status: 'failed',
        );
    }

    /**
     * Create a denied result (permission denied).
     *
     * @param  string  $reason  The reason for denial
     */
    public static function denied(string $reason): self
    {
        return new self(
            success: false,
            data: null,
            error: $reason,
            executionTimeMs: 0,
            status: 'denied',
        );
    }

    /**
     * Check if the result indicates permission was denied.
     */
    public function isDenied(): bool
    {
        return $this->status === 'denied';
    }

    /**
     * Check if the result indicates a failure (not including denied).
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Convert to array for logging or serialization.
     *
     * @return array{success: bool, data: mixed, error: ?string, execution_time_ms: int, status: string}
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'error' => $this->error,
            'execution_time_ms' => $this->executionTimeMs,
            'status' => $this->status,
        ];
    }
}
