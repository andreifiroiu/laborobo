<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MessageType;
use App\Models\CommunicationThread;
use App\Models\Message;
use App\Models\Project;
use App\Models\Task;
use App\Models\WorkOrder;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationsController extends Controller
{
    /**
     * Map of filter values to model classes for threadable type filtering.
     */
    private const THREADABLE_TYPE_MAP = [
        'project' => Project::class,
        'work_order' => WorkOrder::class,
        'task' => Task::class,
    ];

    /**
     * Display consolidated communications view with messages across all work items.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $team = $user->currentTeam;

        if (! $team) {
            return Inertia::render('communications/index', [
                'messages' => [
                    'data' => [],
                    'meta' => $this->buildPaginationMeta(0, 1, 20),
                ],
                'filters' => $this->buildFiltersResponse($request),
                'filterOptions' => $this->getFilterOptions(),
            ]);
        }

        // Get thread IDs for this team
        $teamThreadIds = CommunicationThread::forTeam($team->id)->pluck('id');

        // Build base query for messages
        $query = Message::whereIn('communication_thread_id', $teamThreadIds)
            ->with([
                'communicationThread.threadable',
                'author',
                'mentions.mentionable',
                'attachments',
                'reactions',
            ])
            ->orderBy('created_at', 'desc');

        // Apply threadable type filter
        $query = $this->applyTypeFilter($query, $request->input('type'), $team->id);

        // Apply message type filter
        $query = $this->applyMessageTypeFilter($query, $request->input('message_type'));

        // Apply date range filter
        $query = $this->applyDateRangeFilter(
            $query,
            $request->input('from'),
            $request->input('to')
        );

        // Apply search filter
        $query = $this->applySearchFilter($query, $request->input('search'));

        // Paginate results
        $perPage = min((int) $request->input('per_page', 20), 100);
        $paginated = $query->paginate($perPage);

        // Format messages for response
        $formattedMessages = $paginated->getCollection()->map(
            fn (Message $message) => $this->formatMessage($message, $user->id)
        );

        return Inertia::render('communications/index', [
            'messages' => [
                'data' => $formattedMessages->values()->all(),
                'meta' => $this->buildPaginationMeta(
                    $paginated->total(),
                    $paginated->currentPage(),
                    $paginated->perPage(),
                    $paginated->lastPage()
                ),
                'links' => [
                    'first' => $paginated->url(1),
                    'last' => $paginated->url($paginated->lastPage()),
                    'prev' => $paginated->previousPageUrl(),
                    'next' => $paginated->nextPageUrl(),
                ],
            ],
            'filters' => $this->buildFiltersResponse($request),
            'filterOptions' => $this->getFilterOptions(),
        ]);
    }

    /**
     * Apply threadable type filter to the query.
     */
    private function applyTypeFilter(Builder $query, ?string $type, int $teamId): Builder
    {
        if ($type === null || ! array_key_exists($type, self::THREADABLE_TYPE_MAP)) {
            return $query;
        }

        $threadableClass = self::THREADABLE_TYPE_MAP[$type];

        $threadIds = CommunicationThread::where('team_id', $teamId)
            ->where('threadable_type', $threadableClass)
            ->pluck('id');

        return $query->whereIn('communication_thread_id', $threadIds);
    }

    /**
     * Apply message type filter to the query.
     */
    private function applyMessageTypeFilter(Builder $query, ?string $messageType): Builder
    {
        if ($messageType === null) {
            return $query;
        }

        $validTypes = array_map(fn (MessageType $type) => $type->value, MessageType::cases());

        if (! in_array($messageType, $validTypes, true)) {
            return $query;
        }

        return $query->where('type', $messageType);
    }

    /**
     * Apply date range filter to the query.
     */
    private function applyDateRangeFilter(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }

        return $query;
    }

    /**
     * Apply search filter using LIKE on message content.
     */
    private function applySearchFilter(Builder $query, ?string $search): Builder
    {
        if ($search === null || trim($search) === '') {
            return $query;
        }

        $searchTerm = '%'.trim($search).'%';

        return $query->where('content', 'LIKE', $searchTerm);
    }

    /**
     * Format a message for the API response with work item context.
     *
     * @return array<string, mixed>
     */
    private function formatMessage(Message $message, int $currentUserId): array
    {
        $thread = $message->communicationThread;
        $threadable = $thread?->threadable;

        $canEditOrDelete = $message->author_id === $currentUserId
            && $message->created_at->diffInMinutes(now()) <= 10;

        // Group reactions by emoji with counts
        $reactions = $message->reactions->groupBy('emoji')->map(function ($group, $emoji) use ($currentUserId) {
            return [
                'emoji' => $emoji,
                'count' => $group->count(),
                'hasReacted' => $group->contains('user_id', $currentUserId),
                'users' => $group->map(fn ($r) => [
                    'id' => (string) $r->user_id,
                ])->values()->all(),
            ];
        })->values()->all();

        return [
            'id' => (string) $message->id,
            'threadId' => (string) $message->communication_thread_id,
            'authorId' => (string) $message->author_id,
            'authorName' => $message->author?->name ?? 'Unknown',
            'authorType' => $message->author_type->value,
            'timestamp' => $message->created_at->toIso8601String(),
            'content' => $message->content,
            'type' => $message->type->value,
            'typeLabel' => $message->type->label(),
            'typeColor' => $message->type->color(),
            'editedAt' => $message->edited_at?->toIso8601String(),
            'canEdit' => $canEditOrDelete,
            'canDelete' => $canEditOrDelete,
            'mentions' => $message->mentions->map(fn ($m) => [
                'id' => (string) $m->id,
                'type' => $m->mentionable_type,
                'entityId' => (string) $m->mentionable_id,
                'name' => $m->mentionable?->name ?? $m->mentionable?->title ?? 'Unknown',
            ])->all(),
            'attachments' => $message->attachments->map(fn ($a) => [
                'id' => (string) $a->id,
                'name' => $a->name,
                'fileUrl' => Storage::disk('public')->url($a->file_url),
                'fileSize' => $a->file_size,
                'mimeType' => $a->mime_type,
            ])->all(),
            'reactions' => $reactions,
            'workItem' => $this->formatWorkItem($thread, $threadable),
        ];
    }

    /**
     * Format work item context for the message.
     *
     * @return array<string, mixed>|null
     */
    private function formatWorkItem(?CommunicationThread $thread, mixed $threadable): ?array
    {
        if ($thread === null || $threadable === null) {
            return null;
        }

        $type = match ($thread->threadable_type) {
            Project::class => 'project',
            WorkOrder::class => 'work_order',
            Task::class => 'task',
            default => 'unknown',
        };

        $route = match ($thread->threadable_type) {
            Project::class => route('projects.show', $threadable->id),
            WorkOrder::class => route('work-orders.show', $threadable->id),
            Task::class => route('tasks.show', $threadable->id),
            default => null,
        };

        return [
            'type' => $type,
            'typeLabel' => ucfirst(str_replace('_', ' ', $type)),
            'id' => (string) $threadable->id,
            'name' => $threadable->name ?? $threadable->title ?? 'Unnamed',
            'route' => $route,
        ];
    }

    /**
     * Build pagination meta information.
     *
     * @return array<string, mixed>
     */
    private function buildPaginationMeta(int $total, int $currentPage, int $perPage, ?int $lastPage = null): array
    {
        return [
            'total' => $total,
            'currentPage' => $currentPage,
            'perPage' => $perPage,
            'lastPage' => $lastPage ?? (int) ceil($total / $perPage) ?: 1,
        ];
    }

    /**
     * Build filters response with active values.
     *
     * @return array<string, mixed>
     */
    private function buildFiltersResponse(Request $request): array
    {
        return [
            'type' => $request->input('type'),
            'message_type' => $request->input('message_type'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'search' => $request->input('search'),
        ];
    }

    /**
     * Get available filter options.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    private function getFilterOptions(): array
    {
        return [
            'types' => [
                ['value' => 'project', 'label' => 'Project'],
                ['value' => 'work_order', 'label' => 'Work Order'],
                ['value' => 'task', 'label' => 'Task'],
            ],
            'messageTypes' => array_map(
                fn (MessageType $type) => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'color' => $type->color(),
                ],
                MessageType::cases()
            ),
        ];
    }
}
