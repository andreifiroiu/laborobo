<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Contracts\Tools\ToolInterface;
use App\Models\AgentActivityLog;
use InvalidArgumentException;

/**
 * Tool for creating notes on various entities.
 *
 * This tool allows agents to add notes and comments to tasks, work orders,
 * projects, and other entities for documentation and communication purposes.
 *
 * Notes created by this tool are stored in the AgentActivityLog as a record
 * of agent-generated content that can be reviewed and used by team members.
 */
class CreateNoteTool implements ToolInterface
{
    /**
     * Get the unique identifier name for this tool.
     */
    public function name(): string
    {
        return 'create-note';
    }

    /**
     * Get a human-readable description of what this tool does.
     */
    public function description(): string
    {
        return 'Creates a note or comment on an entity (task, work order, project). Notes are stored for human review and can be used for documentation or communication purposes.';
    }

    /**
     * Get the category this tool belongs to.
     */
    public function category(): string
    {
        return 'general';
    }

    /**
     * Execute the tool with the given parameters.
     *
     * @param  array<string, mixed>  $params  The parameters for tool execution
     * @return array<string, mixed> The result data from execution
     *
     * @throws InvalidArgumentException If required parameters are missing
     */
    public function execute(array $params): array
    {
        $entityType = $params['entity_type'] ?? null;
        $entityId = $params['entity_id'] ?? null;
        $content = $params['content'] ?? null;
        $noteType = $params['note_type'] ?? 'general';
        $agentId = $params['agent_id'] ?? null;
        $teamId = $params['team_id'] ?? null;

        // Validate required parameters
        if ($entityType === null) {
            throw new InvalidArgumentException('entity_type is required');
        }

        if ($entityId === null) {
            throw new InvalidArgumentException('entity_id is required');
        }

        if ($content === null || trim($content) === '') {
            throw new InvalidArgumentException('content is required and cannot be empty');
        }

        // Validate entity type
        $validEntityTypes = ['task', 'work_order', 'project', 'party', 'deliverable'];
        if (! in_array($entityType, $validEntityTypes, true)) {
            throw new InvalidArgumentException(
                'entity_type must be one of: '.implode(', ', $validEntityTypes)
            );
        }

        // Validate note type
        $validNoteTypes = ['general', 'status_update', 'feedback', 'decision', 'blocker', 'question'];
        if (! in_array($noteType, $validNoteTypes, true)) {
            throw new InvalidArgumentException(
                'note_type must be one of: '.implode(', ', $validNoteTypes)
            );
        }

        // Create the note record
        $noteData = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'content' => $content,
            'note_type' => $noteType,
            'created_at' => now()->toDateTimeString(),
        ];

        // If agent context is provided, log to AgentActivityLog
        if ($agentId !== null && $teamId !== null) {
            $activityLog = AgentActivityLog::create([
                'team_id' => $teamId,
                'ai_agent_id' => $agentId,
                'run_type' => 'note_creation',
                'input' => json_encode([
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'note_type' => $noteType,
                ]),
                'output' => $content,
                'tool_calls' => [
                    [
                        'tool' => 'create-note',
                        'params' => $params,
                        'result' => $noteData,
                    ],
                ],
                'context_accessed' => [
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                ],
            ]);

            $noteData['activity_log_id'] = $activityLog->id;
        }

        return [
            'note' => $noteData,
            'success' => true,
            'message' => "Note created successfully on {$entityType} #{$entityId}",
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
                'description' => 'The type of entity to attach the note to (task, work_order, project, party, deliverable)',
                'required' => true,
            ],
            'entity_id' => [
                'type' => 'integer',
                'description' => 'The ID of the entity to attach the note to',
                'required' => true,
            ],
            'content' => [
                'type' => 'string',
                'description' => 'The content of the note',
                'required' => true,
            ],
            'note_type' => [
                'type' => 'string',
                'description' => 'The type of note (general, status_update, feedback, decision, blocker, question). Default: general',
                'required' => false,
            ],
            'agent_id' => [
                'type' => 'integer',
                'description' => 'The ID of the AI agent creating the note (for activity logging)',
                'required' => false,
            ],
            'team_id' => [
                'type' => 'integer',
                'description' => 'The ID of the team (for activity logging)',
                'required' => false,
            ],
        ];
    }
}
