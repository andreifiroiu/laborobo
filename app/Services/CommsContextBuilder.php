<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CommunicationType;
use App\Enums\PlaybookType;
use App\Models\CommunicationThread;
use App\Models\Deliverable;
use App\Models\Party;
use App\Models\Playbook;
use App\Models\Project;
use App\Models\StatusTransition;
use App\Models\WorkOrder;
use App\ValueObjects\AgentContext;
use Illuminate\Support\Collection;

/**
 * Service for building communication-specific context for the Client Comms Agent.
 *
 * Extends the base ContextBuilder pattern to gather communication-specific context
 * including work item details, conversation history, templates, and Party preferences.
 */
class CommsContextBuilder
{
    private const STATUS_TRANSITION_LIMIT = 5;

    private const DELIVERABLE_LIMIT = 10;

    private const MESSAGE_LIMIT = 10;

    public function __construct(
        private readonly ContextBuilder $contextBuilder,
    ) {}

    /**
     * Build context for a work item (Project or WorkOrder).
     *
     * Includes title, description, status, progress, recent status transitions,
     * attached deliverables and their states, and key milestones.
     *
     * @return array<string, mixed>
     */
    public function buildWorkItemContext(Project|WorkOrder $entity): array
    {
        $context = [
            'entity_type' => class_basename($entity),
            'entity_id' => $entity->id,
        ];

        if ($entity instanceof Project) {
            $context = array_merge($context, $this->buildProjectWorkItemContext($entity));
        } else {
            $context = array_merge($context, $this->buildWorkOrderWorkItemContext($entity));
        }

        return $context;
    }

    /**
     * Build context from a CommunicationThread's message history.
     *
     * Gets recent messages with author info, timestamps, and message types.
     * Filters out internal-only messages if applicable.
     *
     * @return array<string, mixed>
     */
    public function buildThreadHistoryContext(CommunicationThread $thread, int $limit = self::MESSAGE_LIMIT): array
    {
        $messages = $thread->messages()
            ->with('author')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return [
            'thread_id' => $thread->id,
            'message_count' => $thread->message_count,
            'last_activity' => $thread->last_activity?->toIso8601String(),
            'messages' => $messages->map(fn ($message) => [
                'id' => $message->id,
                'content' => $this->truncateText($message->content, 500),
                'type' => $message->type?->value ?? $message->type,
                'author_name' => $message->author?->name ?? 'Unknown',
                'author_type' => $message->author_type?->value ?? $message->author_type,
                'created_at' => $message->created_at?->toIso8601String(),
            ])->toArray(),
        ];
    }

    /**
     * Find communication templates from Playbooks matching the type and tags.
     *
     * Queries Playbooks with type 'template' or communication-related tags.
     * Returns ordered by relevance (tag matches).
     *
     * @param  array<string>  $tags  Additional tags to filter by
     * @return Collection<int, Playbook>
     */
    public function findCommunicationTemplates(CommunicationType $type, array $tags = []): Collection
    {
        $communicationTypeTag = $type->value;
        $allTags = array_merge([$communicationTypeTag], $tags);

        return Playbook::where('type', PlaybookType::Template)
            ->where(function ($query) use ($allTags) {
                foreach ($allTags as $tag) {
                    $query->whereJsonContains('tags', $tag);
                }
            })
            ->orderByDesc('times_applied')
            ->get();
    }

    /**
     * Build context for a Party (client).
     *
     * Includes contact name, email, preferred language, relationship history,
     * and communication preferences.
     *
     * @return array<string, mixed>
     */
    public function buildPartyContext(Party $party): array
    {
        $party->load(['projects', 'contacts']);

        $firstProject = $party->projects()
            ->orderBy('created_at')
            ->first();

        $relationshipDuration = $firstProject
            ? $firstProject->created_at->diffForHumans(now(), true)
            : null;

        return [
            'id' => $party->id,
            'name' => $party->name,
            'type' => $party->type?->value ?? 'unknown',
            'contact_name' => $party->contact_name,
            'contact_email' => $party->contact_email ?? $party->email,
            'preferred_language' => $party->preferred_language,
            'projects_count' => $party->projects()->count(),
            'relationship_duration' => $relationshipDuration,
            'notes' => $this->truncateText($party->notes, 300),
            'tags' => $party->tags ?? [],
        ];
    }

    /**
     * Build unified context for agent consumption.
     *
     * Combines work item context, Party context, conversation history,
     * and template information into a single AgentContext object.
     */
    public function buildFullContext(Project|WorkOrder $entity, CommunicationType $type): AgentContext
    {
        $project = $entity instanceof Project ? $entity : $entity->project;
        $party = $project?->party;

        // Build work item context
        $workItemContext = $this->buildWorkItemContext($entity);

        // Build project context using the base ContextBuilder
        $projectContext = $project ? $this->contextBuilder->buildProjectContext($project) : [];

        // Merge work item specific context into project context
        $projectContext = array_merge($projectContext, [
            'work_item' => $workItemContext,
        ]);

        // Add templates to project context if found
        $templates = $this->findCommunicationTemplates($type);
        if ($templates->isNotEmpty()) {
            $projectContext['available_templates'] = $templates->map(fn ($t) => [
                'name' => $t->name,
                'description' => $t->description,
            ])->toArray();
        }

        // Build Party/client context
        $clientContext = $party ? $this->buildPartyContext($party) : [];

        // Build conversation history if thread exists
        $thread = $entity->communicationThread;
        if ($thread !== null) {
            $threadHistory = $this->buildThreadHistoryContext($thread);
            $projectContext['conversation_history'] = $threadHistory;
        }

        // Build org context via base builder
        $orgContext = [];
        if ($project?->team !== null) {
            $orgContext = $this->contextBuilder->buildOrgContext($project->team);
        }

        // Determine target language from Party preferences
        $targetLanguage = $party?->preferred_language ?? 'en';

        return new AgentContext(
            projectContext: $projectContext,
            clientContext: $clientContext,
            orgContext: $orgContext,
            metadata: [
                'communication_type' => $type->value,
                'communication_type_label' => $type->label(),
                'target_language' => $targetLanguage,
                'entity_type' => class_basename($entity),
                'entity_id' => $entity->id,
                'built_at' => now()->toIso8601String(),
            ],
        );
    }

    /**
     * Build work item context specific to a Project.
     *
     * @return array<string, mixed>
     */
    private function buildProjectWorkItemContext(Project $project): array
    {
        $project->load(['workOrders', 'party']);

        // Get deliverables through work orders
        $deliverables = Deliverable::whereIn(
            'work_order_id',
            $project->workOrders()->pluck('id')
        )
            ->orderByDesc('updated_at')
            ->limit(self::DELIVERABLE_LIMIT)
            ->get();

        // Get recent status transitions from work orders
        $workOrderIds = $project->workOrders()->pluck('id')->toArray();
        $recentTransitions = StatusTransition::where('transitionable_type', WorkOrder::class)
            ->whereIn('transitionable_id', $workOrderIds)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(self::STATUS_TRANSITION_LIMIT)
            ->get();

        return [
            'title' => $project->name,
            'description' => $this->truncateText($project->description, 500),
            'status' => $project->status?->value ?? 'unknown',
            'progress' => $project->progress ?? 0,
            'start_date' => $project->start_date?->toDateString(),
            'target_end_date' => $project->target_end_date?->toDateString(),
            'budget_hours' => $project->budget_hours,
            'actual_hours' => $project->actual_hours,
            'work_orders_count' => $project->workOrders()->count(),
            'deliverables' => $deliverables->map(fn (Deliverable $d) => [
                'id' => $d->id,
                'title' => $d->title,
                'status' => $d->status?->value ?? 'unknown',
                'delivered_date' => $d->delivered_date?->toDateString(),
            ])->toArray(),
            'recent_status_transitions' => $recentTransitions->map(fn (StatusTransition $t) => [
                'from_status' => $t->from_status,
                'to_status' => $t->to_status,
                'changed_by' => $t->user?->name ?? 'System',
                'changed_at' => $t->created_at?->toIso8601String(),
            ])->toArray(),
        ];
    }

    /**
     * Build work item context specific to a WorkOrder.
     *
     * @return array<string, mixed>
     */
    private function buildWorkOrderWorkItemContext(WorkOrder $workOrder): array
    {
        $workOrder->load(['project', 'deliverables', 'tasks']);

        // Get recent status transitions for this work order
        $recentTransitions = $workOrder->statusTransitions()
            ->with('user')
            ->limit(self::STATUS_TRANSITION_LIMIT)
            ->get();

        // Calculate progress from tasks
        $totalTasks = $workOrder->tasks()->count();
        $completedTasks = $workOrder->tasks()->where('status', 'done')->count();
        $progress = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;

        return [
            'title' => $workOrder->title,
            'description' => $this->truncateText($workOrder->description, 500),
            'status' => $workOrder->status?->value ?? 'unknown',
            'progress' => $progress,
            'priority' => $workOrder->priority?->value ?? 'normal',
            'due_date' => $workOrder->due_date?->toDateString(),
            'estimated_hours' => $workOrder->estimated_hours,
            'actual_hours' => $workOrder->actual_hours,
            'project_name' => $workOrder->project?->name,
            'project_id' => $workOrder->project_id,
            'tasks_count' => $totalTasks,
            'completed_tasks_count' => $completedTasks,
            'deliverables' => $workOrder->deliverables
                ->map(fn (Deliverable $d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'status' => $d->status?->value ?? 'unknown',
                    'delivered_date' => $d->delivered_date?->toDateString(),
                ])
                ->toArray(),
            'recent_status_transitions' => $recentTransitions->map(fn (StatusTransition $t) => [
                'from_status' => $t->from_status,
                'to_status' => $t->to_status,
                'changed_by' => $t->user?->name ?? 'System',
                'changed_at' => $t->created_at?->toIso8601String(),
            ])->toArray(),
            'acceptance_criteria' => $workOrder->acceptance_criteria ?? [],
        ];
    }

    /**
     * Truncate text to a maximum length with ellipsis.
     */
    private function truncateText(?string $text, int $maxLength): ?string
    {
        if ($text === null || strlen($text) <= $maxLength) {
            return $text;
        }

        return substr($text, 0, $maxLength - 3) . '...';
    }
}
