<?php

namespace App\Http\Controllers\Work;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\WorkOrder;
use App\Services\WorkflowTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function __construct(
        private readonly WorkflowTransitionService $transitionService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'workOrderId' => 'required|exists:work_orders,id',
            'assignedToId' => 'nullable|exists:users,id',
            'dueDate' => 'required|date',
            'estimatedHours' => 'nullable|numeric|min:0',
            'checklistItems' => 'nullable|array',
        ]);

        $user = $request->user();
        $team = $user->currentTeam;
        $workOrder = WorkOrder::findOrFail($validated['workOrderId']);

        // Format checklist items with IDs if not present
        $checklistItems = collect($validated['checklistItems'] ?? [])->map(function ($item) {
            if (is_string($item)) {
                return [
                    'id' => Str::uuid()->toString(),
                    'text' => $item,
                    'completed' => false,
                ];
            }
            return $item;
        })->all();

        Task::create([
            'team_id' => $team->id,
            'work_order_id' => $validated['workOrderId'],
            'project_id' => $workOrder->project_id,
            'assigned_to_id' => $validated['assignedToId'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => TaskStatus::Todo,
            'due_date' => $validated['dueDate'],
            'estimated_hours' => $validated['estimatedHours'] ?? 0,
            'checklist_items' => $checklistItems,
        ]);

        return back();
    }

    public function show(Request $request, Task $task): Response
    {
        $this->authorize('view', $task);

        $task->load([
            'workOrder',
            'project',
            'assignedTo',
            'timeEntries.user',
            'documents',
            'statusTransitions.user',
        ]);

        // Get active timer if any
        $activeTimer = $task->timeEntries()
            ->whereNotNull('started_at')
            ->whereNull('stopped_at')
            ->where('user_id', $request->user()->id)
            ->first();

        // Get allowed transitions for current user
        $allowedTransitions = $this->getFormattedAllowedTransitions($task, $request->user());

        // Get rejection feedback if applicable (status is InProgress and previous transition was from RevisionRequested)
        $rejectionFeedback = $this->getRejectionFeedback($task);

        return Inertia::render('work/tasks/[id]', [
            'task' => [
                'id' => (string) $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'workOrderId' => (string) $task->work_order_id,
                'workOrderTitle' => $task->workOrder?->title ?? 'Unknown',
                'projectId' => (string) $task->project_id,
                'projectName' => $task->project?->name ?? 'Unknown',
                'assignedToId' => $task->assigned_to_id ? (string) $task->assigned_to_id : null,
                'assignedToName' => $task->assignedTo?->name ?? 'Unassigned',
                'status' => $task->status->value,
                'dueDate' => $task->due_date->format('Y-m-d'),
                'estimatedHours' => (float) $task->estimated_hours,
                'actualHours' => (float) $task->actual_hours,
                'checklistItems' => $task->checklist_items ?? [],
                'dependencies' => $task->dependencies ?? [],
                'isBlocked' => $task->is_blocked,
            ],
            'timeEntries' => $task->timeEntries->map(fn (TimeEntry $entry) => [
                'id' => (string) $entry->id,
                'userId' => (string) $entry->user_id,
                'userName' => $entry->user?->name ?? 'Unknown',
                'hours' => (float) $entry->hours,
                'date' => $entry->date->format('Y-m-d'),
                'mode' => $entry->mode->value,
                'note' => $entry->note,
                'startedAt' => $entry->started_at?->toIso8601String(),
                'stoppedAt' => $entry->stopped_at?->toIso8601String(),
            ]),
            'activeTimer' => $activeTimer ? [
                'id' => (string) $activeTimer->id,
                'startedAt' => $activeTimer->started_at->toIso8601String(),
            ] : null,
            'teamMembers' => $task->project->team->users->map(fn ($user) => [
                'id' => (string) $user->id,
                'name' => $user->name,
            ]),
            'statusTransitions' => $task->statusTransitions->map(fn ($transition) => [
                'id' => $transition->id,
                'fromStatus' => $transition->from_status,
                'toStatus' => $transition->to_status,
                'user' => $transition->user ? [
                    'id' => $transition->user->id,
                    'name' => $transition->user->name,
                    'email' => $transition->user->email,
                    'avatar' => $transition->user->avatar ?? null,
                ] : null,
                'createdAt' => $transition->created_at->toIso8601String(),
                'comment' => $transition->comment,
                'commentCategory' => $transition->comment_category ?? null,
            ]),
            'allowedTransitions' => $allowedTransitions,
            'rejectionFeedback' => $rejectionFeedback,
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'assignedToId' => 'nullable|exists:users,id',
            'dueDate' => 'sometimes|required|date',
            'estimatedHours' => 'nullable|numeric|min:0',
            'checklistItems' => 'nullable|array',
            'isBlocked' => 'sometimes|boolean',
        ]);

        $updateData = [];
        if (isset($validated['title'])) $updateData['title'] = $validated['title'];
        if (array_key_exists('description', $validated)) $updateData['description'] = $validated['description'];
        if (array_key_exists('assignedToId', $validated)) $updateData['assigned_to_id'] = $validated['assignedToId'];
        if (isset($validated['dueDate'])) $updateData['due_date'] = $validated['dueDate'];
        if (array_key_exists('estimatedHours', $validated)) $updateData['estimated_hours'] = $validated['estimatedHours'] ?? 0;
        if (isset($validated['checklistItems'])) $updateData['checklist_items'] = $validated['checklistItems'];
        if (isset($validated['isBlocked'])) $updateData['is_blocked'] = $validated['isBlocked'];

        $task->update($updateData);

        return back();
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $workOrderId = $task->work_order_id;
        $task->delete();

        return redirect()->route('work-orders.show', $workOrderId);
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'status' => 'required|string|in:todo,in_progress,done',
        ]);

        $task->update([
            'status' => TaskStatus::from($validated['status']),
        ]);

        // Update project progress
        $task->project->recalculateProgress();

        return back();
    }

    public function toggleChecklist(Request $request, Task $task, string $itemId): RedirectResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'completed' => 'required|boolean',
        ]);

        $task->toggleChecklistItem($itemId, $validated['completed']);

        return back();
    }

    /**
     * Promote a task to a work order.
     */
    public function promote(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'dueDate' => 'nullable|date',
            'estimatedHours' => 'nullable|numeric|min:0',
            'assignedToId' => 'nullable|exists:users,id',
            'acceptanceCriteria' => 'nullable|array',
            'acceptanceCriteria.*' => 'string',
            'originalTaskAction' => 'required|string|in:cancel,delete,keep',
            'convertChecklistToTasks' => 'boolean',
        ]);

        $user = $request->user();
        $task->load('workOrder.project');

        // Create the new work order
        $workOrder = WorkOrder::create([
            'team_id' => $task->team_id,
            'project_id' => $task->workOrder->project_id,
            'assigned_to_id' => $validated['assignedToId'] ?? $task->assigned_to_id,
            'created_by_id' => $user->id,
            'party_contact_id' => $task->workOrder->project->party_id ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? $task->description,
            'status' => WorkOrderStatus::Draft,
            'priority' => Priority::from($validated['priority']),
            'due_date' => $validated['dueDate'] ?? $task->due_date,
            'estimated_hours' => $validated['estimatedHours'] ?? $task->estimated_hours,
            'acceptance_criteria' => $validated['acceptanceCriteria'] ?? [],
            'accountable_id' => $user->id,
        ]);

        // Optionally convert checklist items to tasks
        if (($validated['convertChecklistToTasks'] ?? false) && ! empty($task->checklist_items)) {
            foreach ($task->checklist_items as $item) {
                Task::create([
                    'team_id' => $task->team_id,
                    'work_order_id' => $workOrder->id,
                    'project_id' => $workOrder->project_id,
                    'assigned_to_id' => $validated['assignedToId'] ?? $task->assigned_to_id,
                    'title' => $item['text'],
                    'description' => null,
                    'status' => $item['completed'] ? TaskStatus::Done : TaskStatus::Todo,
                    'due_date' => $validated['dueDate'] ?? $task->due_date,
                    'estimated_hours' => 0,
                    'checklist_items' => [],
                ]);
            }
        }

        // Handle the original task
        match ($validated['originalTaskAction']) {
            'cancel' => $task->update(['status' => TaskStatus::Cancelled]),
            'delete' => $task->delete(),
            'keep' => null, // Do nothing
        };

        return redirect()->route('work-orders.show', $workOrder->id);
    }

    /**
     * Get formatted allowed transitions for the frontend.
     *
     * @return array<int, array{value: string, label: string, destructive?: bool}>
     */
    private function getFormattedAllowedTransitions(Task $task, $user): array
    {
        $availableTransitions = $this->transitionService->getAvailableTransitions($task, $user);

        $transitionLabels = [
            'in_progress' => ['label' => 'Start Working', 'destructive' => false],
            'in_review' => ['label' => 'Submit for Review', 'destructive' => false],
            'approved' => ['label' => 'Approve', 'destructive' => false],
            'done' => ['label' => 'Mark as Done', 'destructive' => false],
            'blocked' => ['label' => 'Mark as Blocked', 'destructive' => false],
            'cancelled' => ['label' => 'Cancel', 'destructive' => true],
            'revision_requested' => ['label' => 'Request Changes', 'destructive' => false],
        ];

        return array_map(
            fn (string $status) => [
                'value' => $status,
                'label' => $transitionLabels[$status]['label'] ?? ucwords(str_replace('_', ' ', $status)),
                'destructive' => $transitionLabels[$status]['destructive'] ?? false,
            ],
            $availableTransitions
        );
    }

    /**
     * Get rejection feedback if the task was recently sent back for revisions.
     *
     * @return array{comment: string, user: array{id: int, name: string, email: string}, createdAt: string}|null
     */
    private function getRejectionFeedback(Task $task): ?array
    {
        // Only show feedback if current status is InProgress
        if ($task->status !== TaskStatus::InProgress) {
            return null;
        }

        // Find the most recent revision_requested transition
        $revisionTransition = $task->statusTransitions
            ->filter(fn ($t) => $t->to_status === 'revision_requested' && $t->comment !== null)
            ->sortByDesc('created_at')
            ->first();

        if ($revisionTransition === null) {
            return null;
        }

        // Check if there's a transition from revision_requested to in_progress after the rejection
        $autoTransition = $task->statusTransitions
            ->filter(fn ($t) => $t->from_status === 'revision_requested' && $t->to_status === 'in_progress')
            ->sortByDesc('created_at')
            ->first();

        // Only show if the revision happened and auto-transitioned to in_progress
        if ($autoTransition === null || $autoTransition->created_at < $revisionTransition->created_at) {
            return null;
        }

        return [
            'comment' => $revisionTransition->comment,
            'user' => [
                'id' => $revisionTransition->user?->id ?? 0,
                'name' => $revisionTransition->user?->name ?? 'Unknown',
                'email' => $revisionTransition->user?->email ?? '',
            ],
            'createdAt' => $revisionTransition->created_at->toIso8601String(),
        ];
    }
}
