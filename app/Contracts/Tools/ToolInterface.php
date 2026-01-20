<?php

declare(strict_types=1);

namespace App\Contracts\Tools;

/**
 * Contract that all agent tools must implement.
 *
 * Tools are executed through the ToolGateway which handles all permission
 * checks. Tools must NOT self-check permissions; the gateway handles
 * all authorization.
 */
interface ToolInterface
{
    /**
     * Get the unique identifier name for this tool.
     */
    public function name(): string;

    /**
     * Get a human-readable description of what this tool does.
     */
    public function description(): string;

    /**
     * Get the category this tool belongs to.
     *
     * Categories are used for permission grouping (e.g., 'tasks', 'work_orders',
     * 'client_data', 'email', 'deliverables', 'financial', 'playbooks').
     */
    public function category(): string;

    /**
     * Execute the tool with the given parameters.
     *
     * @param  array<string, mixed>  $params  The parameters for tool execution
     * @return array<string, mixed> The result data from execution
     */
    public function execute(array $params): array;

    /**
     * Get the parameter definitions for this tool.
     *
     * Returns an array describing the expected parameters, their types,
     * and whether they are required.
     *
     * @return array<string, array{type: string, description: string, required: bool}>
     */
    public function getParameters(): array;
}
