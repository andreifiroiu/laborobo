<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AgentMemoryScope;
use App\Models\AIAgent;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\WorkOrder;
use App\ValueObjects\AgentContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Service for assembling relevant context for agent runs.
 *
 * Builds context at three levels (project, client, org) based on the
 * entity being operated on. Implements token limit enforcement with
 * intelligent truncation prioritizing recent and relevant data.
 */
class ContextBuilder
{
    /**
     * Default maximum tokens for context (can be overridden per call).
     */
    private const DEFAULT_MAX_TOKENS = 4000;

    /**
     * Characters per token for estimation.
     */
    private const CHARS_PER_TOKEN = 4;

    public function __construct(
        private readonly AgentMemoryService $memoryService,
    ) {}

    /**
     * Build context for an agent run based on the entity being operated on.
     *
     * @param  Model  $entity  The entity being operated on (Task, WorkOrder, Project, Party)
     * @param  AIAgent  $agent  The agent that will use this context
     * @param  int  $maxTokens  Maximum tokens for the context (default 4000)
     * @return AgentContext The assembled context
     */
    public function build(Model $entity, AIAgent $agent, int $maxTokens = self::DEFAULT_MAX_TOKENS): AgentContext
    {
        $projectContext = [];
        $clientContext = [];
        $orgContext = [];
        $metadata = [
            'entity_type' => class_basename($entity),
            'entity_id' => $entity->getKey(),
            'agent_id' => $agent->id,
            'built_at' => now()->toIso8601String(),
        ];

        // Determine context hierarchy based on entity type
        $contextHierarchy = $this->resolveContextHierarchy($entity);

        if ($contextHierarchy['project'] !== null) {
            $projectContext = $this->buildProjectContext($contextHierarchy['project']);
        }

        if ($contextHierarchy['party'] !== null) {
            $clientContext = $this->buildClientContext($contextHierarchy['party']);
        }

        if ($contextHierarchy['team'] !== null) {
            $orgContext = $this->buildOrgContext($contextHierarchy['team']);

            // Add stored memories for each scope
            $this->appendStoredMemories(
                $contextHierarchy['team'],
                $projectContext,
                $clientContext,
                $orgContext,
                $contextHierarchy
            );
        }

        $context = new AgentContext(
            projectContext: $projectContext,
            clientContext: $clientContext,
            orgContext: $orgContext,
            metadata: $metadata,
        );

        // Truncate if necessary to fit within token limit
        return $this->truncateContext($context, $maxTokens);
    }

    /**
     * Build context for a project.
     *
     * @return array<string, mixed>
     */
    public function buildProjectContext(Project $project): array
    {
        $project->load(['workOrders.tasks', 'party', 'owner']);

        $context = [
            'name' => $project->name,
            'description' => $project->description,
            'status' => $project->status?->value ?? 'unknown',
            'start_date' => $project->start_date?->toDateString(),
            'target_end_date' => $project->target_end_date?->toDateString(),
            'progress' => $project->progress ?? 0,
            'budget_hours' => $project->budget_hours,
            'actual_hours' => $project->actual_hours,
            'tags' => $project->tags ?? [],
        ];

        // Add summary of work orders (limited to most recent)
        $workOrders = $project->workOrders()
            ->orderByDesc('work_orders.updated_at')
            ->limit(5)
            ->get();

        if ($workOrders->isNotEmpty()) {
            $context['recent_work_orders'] = $workOrders->map(fn (WorkOrder $wo) => [
                'id' => $wo->id,
                'title' => $wo->title,
                'status' => $wo->status?->value ?? 'unknown',
                'task_count' => $wo->tasks()->count(),
                'completed_tasks' => $wo->tasks()->where('tasks.status', 'done')->count(),
            ])->toArray();
        }

        // Add task summary (limited to recent incomplete tasks)
        // Use direct query on Task model to avoid ambiguous column issues
        $tasks = Task::whereIn('work_order_id', $project->workOrders()->pluck('id'))
            ->where('tasks.status', '!=', 'done')
            ->orderByDesc('tasks.updated_at')
            ->limit(10)
            ->get();

        if ($tasks->isNotEmpty()) {
            $context['pending_tasks'] = $tasks->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status?->value ?? 'unknown',
                'due_date' => $task->due_date?->toDateString(),
                'is_blocked' => $task->is_blocked,
            ])->toArray();
        }

        return $context;
    }

    /**
     * Build context for a client/party.
     *
     * @return array<string, mixed>
     */
    public function buildClientContext(Party $party): array
    {
        $party->load(['contacts', 'projects']);

        $context = [
            'name' => $party->name,
            'type' => $party->type?->value ?? 'unknown',
            'contact_name' => $party->contact_name,
            'contact_email' => $party->contact_email ?? $party->email,
            'status' => $party->status ?? 'active',
            'notes' => $this->truncateText($party->notes, 500),
            'tags' => $party->tags ?? [],
        ];

        // Add project summary
        $activeProjects = $party->projects()
            ->where('projects.status', '!=', 'archived')
            ->orderByDesc('projects.updated_at')
            ->limit(5)
            ->get();

        if ($activeProjects->isNotEmpty()) {
            $context['active_projects'] = $activeProjects->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status?->value ?? 'unknown',
                'progress' => $project->progress ?? 0,
            ])->toArray();
        }

        // Add contact summary
        $contacts = $party->contacts()->limit(3)->get();
        if ($contacts->isNotEmpty()) {
            $context['contacts'] = $contacts->map(fn ($contact) => [
                'name' => $contact->name,
                'email' => $contact->email,
                'role' => $contact->role ?? null,
            ])->toArray();
        }

        return $context;
    }

    /**
     * Build context for an organization/team.
     *
     * @return array<string, mixed>
     */
    public function buildOrgContext(Team $team): array
    {
        $context = [
            'name' => $team->name,
        ];

        // Add team statistics
        $context['statistics'] = [
            'active_projects' => Project::forTeam($team->id)
                ->notArchived()
                ->count(),
            'total_parties' => Party::forTeam($team->id)->count(),
        ];

        return $context;
    }

    /**
     * Resolve the context hierarchy from an entity.
     *
     * Walks up the relationship chain to find project, party, and team.
     *
     * @return array{project: Project|null, party: Party|null, team: Team|null}
     */
    private function resolveContextHierarchy(Model $entity): array
    {
        $project = null;
        $party = null;
        $team = null;

        if ($entity instanceof Task) {
            $project = $entity->project ?? $entity->workOrder?->project;
            $party = $project?->party;
            $team = $entity->team ?? $project?->team;
        } elseif ($entity instanceof WorkOrder) {
            $project = $entity->project;
            $party = $project?->party;
            $team = $entity->team ?? $project?->team;
        } elseif ($entity instanceof Project) {
            $project = $entity;
            $party = $entity->party;
            $team = $entity->team;
        } elseif ($entity instanceof Party) {
            $party = $entity;
            $team = $entity->team;
            // Get the most recent active project for this party
            $project = $party->projects()
                ->where('projects.status', '!=', 'archived')
                ->orderByDesc('projects.updated_at')
                ->first();
        } elseif ($entity instanceof Team) {
            $team = $entity;
        }

        return [
            'project' => $project,
            'party' => $party,
            'team' => $team,
        ];
    }

    /**
     * Append stored memories to the context arrays.
     *
     * @param  array<string, mixed>  $projectContext
     * @param  array<string, mixed>  $clientContext
     * @param  array<string, mixed>  $orgContext
     * @param  array{project: Project|null, party: Party|null, team: Team|null}  $hierarchy
     */
    private function appendStoredMemories(
        Team $team,
        array &$projectContext,
        array &$clientContext,
        array &$orgContext,
        array $hierarchy,
    ): void {
        // Append project memories
        if ($hierarchy['project'] !== null) {
            $projectMemories = $this->memoryService->getForScope(
                $team,
                AgentMemoryScope::Project->value,
                $hierarchy['project']->id
            );

            if ($projectMemories->isNotEmpty()) {
                $projectContext['stored_memories'] = $projectMemories->pluck('value', 'key')->toArray();
            }
        }

        // Append client memories
        if ($hierarchy['party'] !== null) {
            $clientMemories = $this->memoryService->getForScope(
                $team,
                AgentMemoryScope::Client->value,
                $hierarchy['party']->id
            );

            if ($clientMemories->isNotEmpty()) {
                $clientContext['stored_memories'] = $clientMemories->pluck('value', 'key')->toArray();
            }
        }

        // Append org memories
        $orgMemories = $this->memoryService->getForScope(
            $team,
            AgentMemoryScope::Org->value,
            $team->id
        );

        if ($orgMemories->isNotEmpty()) {
            $orgContext['stored_memories'] = $orgMemories->pluck('value', 'key')->toArray();
        }
    }

    /**
     * Truncate context to fit within token limit.
     *
     * Prioritizes recent data and essential information when truncation is needed.
     */
    private function truncateContext(AgentContext $context, int $maxTokens): AgentContext
    {
        if ($context->getTokenEstimate() <= $maxTokens) {
            return $context;
        }

        // Calculate target token allocation per section
        // Prioritize project context > client context > org context
        $totalSections = 3;
        $baseAllocation = (int) ($maxTokens / $totalSections);

        $projectTokens = (int) ($baseAllocation * 1.5);
        $clientTokens = $baseAllocation;
        $orgTokens = (int) ($baseAllocation * 0.5);

        // Truncate each section
        $truncatedProject = $this->truncateContextSection($context->projectContext, $projectTokens);
        $truncatedClient = $this->truncateContextSection($context->clientContext, $clientTokens);
        $truncatedOrg = $this->truncateContextSection($context->orgContext, $orgTokens);

        return new AgentContext(
            projectContext: $truncatedProject,
            clientContext: $truncatedClient,
            orgContext: $truncatedOrg,
            metadata: array_merge($context->metadata, ['truncated' => true]),
        );
    }

    /**
     * Truncate a context section to fit within a token limit.
     *
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private function truncateContextSection(array $section, int $maxTokens): array
    {
        if (empty($section)) {
            return $section;
        }

        $currentTokens = $this->estimateTokens(json_encode($section) ?: '');

        if ($currentTokens <= $maxTokens) {
            return $section;
        }

        // Remove fields progressively, starting with less important ones
        $lowPriorityFields = ['stored_memories', 'tags', 'notes', 'statistics', 'contacts'];
        $truncated = $section;

        foreach ($lowPriorityFields as $field) {
            if (isset($truncated[$field])) {
                unset($truncated[$field]);

                if ($this->estimateTokens(json_encode($truncated) ?: '') <= $maxTokens) {
                    return $truncated;
                }
            }
        }

        // If still too large, truncate array fields
        $arrayFields = ['recent_work_orders', 'pending_tasks', 'active_projects'];

        foreach ($arrayFields as $field) {
            if (isset($truncated[$field]) && is_array($truncated[$field])) {
                $truncated[$field] = array_slice($truncated[$field], 0, 3);

                if ($this->estimateTokens(json_encode($truncated) ?: '') <= $maxTokens) {
                    return $truncated;
                }
            }
        }

        // Final fallback: keep only essential fields
        $essentialFields = ['name', 'status', 'description', 'type'];

        return array_intersect_key($truncated, array_flip($essentialFields));
    }

    /**
     * Estimate token count for a string.
     */
    private function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / self::CHARS_PER_TOKEN);
    }

    /**
     * Truncate text to a maximum length with ellipsis.
     */
    private function truncateText(?string $text, int $maxLength): ?string
    {
        if ($text === null || strlen($text) <= $maxLength) {
            return $text;
        }

        return substr($text, 0, $maxLength - 3).'...';
    }
}
