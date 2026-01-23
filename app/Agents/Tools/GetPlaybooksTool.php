<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Contracts\Tools\ToolInterface;
use App\Models\Playbook;
use App\Models\Team;
use InvalidArgumentException;

/**
 * Tool for searching playbooks (SOPs) by tags and keywords.
 *
 * Queries the Playbook model to find relevant standard operating procedures
 * and templates based on search criteria.
 */
class GetPlaybooksTool implements ToolInterface
{
    /**
     * Get the unique identifier name for this tool.
     */
    public function name(): string
    {
        return 'get-playbooks';
    }

    /**
     * Get a human-readable description of what this tool does.
     */
    public function description(): string
    {
        return 'Searches for relevant playbooks (SOPs and templates) based on tags and keywords to suggest standard procedures for work assignments.';
    }

    /**
     * Get the category this tool belongs to.
     */
    public function category(): string
    {
        return 'playbooks';
    }

    /**
     * Execute the tool with the given parameters.
     *
     * @param  array<string, mixed>  $params  The parameters for tool execution
     * @return array<string, mixed> The result data from execution
     *
     * @throws InvalidArgumentException If team_id is not provided or team not found
     */
    public function execute(array $params): array
    {
        $teamId = $params['team_id'] ?? null;
        $searchQuery = $params['search'] ?? null;
        $tags = $params['tags'] ?? [];
        $type = $params['type'] ?? null;
        $limit = $params['limit'] ?? 10;

        if ($teamId === null) {
            throw new InvalidArgumentException('team_id is required');
        }

        $team = Team::find($teamId);

        if ($team === null) {
            throw new InvalidArgumentException("Team with ID {$teamId} not found");
        }

        // Build the query
        $query = Playbook::query()
            ->forTeam($teamId);

        // Apply type filter if provided
        if ($type !== null) {
            $query->ofType($type);
        }

        // Apply search filter if provided
        if ($searchQuery !== null && $searchQuery !== '') {
            $query->search($searchQuery);
        }

        // Apply tag filters if provided
        if (is_array($tags) && count($tags) > 0) {
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        // Order by usage (most used first) and recency
        $query->orderByDesc('times_applied')
            ->orderByDesc('last_used')
            ->limit($limit);

        $playbooks = $query->get();

        return [
            'team_id' => $teamId,
            'search_criteria' => [
                'search' => $searchQuery,
                'tags' => $tags,
                'type' => $type,
            ],
            'playbooks' => $playbooks->map(fn (Playbook $playbook) => [
                'id' => $playbook->id,
                'name' => $playbook->name,
                'description' => $playbook->description,
                'type' => $playbook->type?->value,
                'tags' => $playbook->tags ?? [],
                'content' => $playbook->content,
                'times_applied' => $playbook->times_applied,
                'last_used' => $playbook->last_used?->toDateTimeString(),
                'ai_generated' => $playbook->ai_generated,
            ])->toArray(),
            'total_found' => $playbooks->count(),
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
            'team_id' => [
                'type' => 'integer',
                'description' => 'The ID of the team to search playbooks for',
                'required' => true,
            ],
            'search' => [
                'type' => 'string',
                'description' => 'Search query to match against playbook name, description, and tags',
                'required' => false,
            ],
            'tags' => [
                'type' => 'array',
                'description' => 'Array of tags to filter playbooks by',
                'required' => false,
            ],
            'type' => [
                'type' => 'string',
                'description' => 'Playbook type to filter by (e.g., checklist, template, sop)',
                'required' => false,
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Maximum number of playbooks to return (default: 10)',
                'required' => false,
            ],
        ];
    }
}
