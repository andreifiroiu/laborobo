<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Contracts\Tools\ToolInterface;
use App\Models\Document;
use App\Models\Project;
use App\Models\WorkOrder;
use InvalidArgumentException;

/**
 * Tool for listing documents attached to projects or work orders.
 *
 * Returns document metadata (name, type, URL, size) without reading file content.
 * Agents can reference documents by name or URL in their responses.
 */
class GetDocumentsTool implements ToolInterface
{
    /**
     * Get the unique identifier name for this tool.
     */
    public function name(): string
    {
        return 'get-documents';
    }

    /**
     * Get a human-readable description of what this tool does.
     */
    public function description(): string
    {
        return 'Lists documents attached to a project or work order, returning metadata such as name, type, file URL, and size.';
    }

    /**
     * Get the category this tool belongs to.
     */
    public function category(): string
    {
        return 'documents';
    }

    /**
     * Execute the tool with the given parameters.
     *
     * @param  array<string, mixed>  $params  The parameters for tool execution
     * @return array<string, mixed> The result data from execution
     *
     * @throws InvalidArgumentException If required parameters are missing or invalid
     */
    public function execute(array $params): array
    {
        $entityType = $params['entity_type'] ?? null;
        $entityId = $params['entity_id'] ?? null;
        $documentType = $params['document_type'] ?? null;
        $search = $params['search'] ?? null;
        $limit = $params['limit'] ?? 20;

        if ($entityType === null) {
            throw new InvalidArgumentException('entity_type is required');
        }

        if ($entityId === null) {
            throw new InvalidArgumentException('entity_id is required');
        }

        if (! in_array($entityType, ['project', 'work_order'], true)) {
            throw new InvalidArgumentException("Invalid entity_type '{$entityType}'. Must be 'project' or 'work_order'.");
        }

        $entity = match ($entityType) {
            'project' => Project::find($entityId),
            'work_order' => WorkOrder::find($entityId),
        };

        if ($entity === null) {
            throw new InvalidArgumentException("{$entityType} with ID {$entityId} not found");
        }

        $query = $entity->documents();

        if ($documentType !== null && $documentType !== '') {
            $query->where('type', $documentType);
        }

        if ($search !== null && $search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $query->orderByDesc('created_at')->limit($limit);

        $documents = $query->get();

        return [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'documents' => $documents->map(fn (Document $doc) => [
                'id' => $doc->id,
                'name' => $doc->name,
                'type' => $doc->type?->value,
                'file_size' => $doc->file_size,
                'file_url' => $doc->file_url,
                'uploaded_at' => $doc->created_at?->toDateTimeString(),
                'folder_id' => $doc->folder_id,
            ])->toArray(),
            'total_found' => $documents->count(),
        ];
    }

    /**
     * Get the parameter definitions for this tool.
     *
     * @return array<string, array{type: string, description: string, required: bool}>
     */
    public function getParameters(): array
    {
        return [
            'entity_type' => [
                'type' => 'string',
                'description' => "The type of entity to list documents for ('project' or 'work_order')",
                'required' => true,
            ],
            'entity_id' => [
                'type' => 'integer',
                'description' => 'The ID of the project or work order',
                'required' => true,
            ],
            'document_type' => [
                'type' => 'string',
                'description' => 'Filter by document type (reference, artifact, evidence, template)',
                'required' => false,
            ],
            'search' => [
                'type' => 'string',
                'description' => 'Search documents by name',
                'required' => false,
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Maximum number of documents to return (default: 20)',
                'required' => false,
            ],
        ];
    }
}
