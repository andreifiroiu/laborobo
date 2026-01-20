<?php

namespace App\Http\Controllers\Work;

use App\Enums\Priority;
use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Message;
use App\Models\Project;
use App\Models\Task;
use App\Models\WorkOrder;
use App\Services\WorkflowTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkOrderController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'projectId' => 'required|exists:projects,id',
            'assignedToId' => 'nullable|exists:users,id',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'dueDate' => 'nullable|date',
            'estimatedHours' => 'nullable|numeric|min:0',
            'acceptanceCriteria' => 'nullable|array',
            'workOrderListId' => 'nullable|exists:work_order_lists,id',
        ]);

        $user = $request->user();
        $team = $user->currentTeam;
        $project = Project::findOrFail($validated['projectId']);

        // Calculate position in list
        $listId = $validated['workOrderListId'] ?? null;
        $positionInList = 0;
        if ($listId) {
            $maxPosition = WorkOrder::where('work_order_list_id', $listId)->max('position_in_list') ?? 0;
            $positionInList = $maxPosition + 100;
        } else {
            $maxPosition = WorkOrder::where('project_id', $validated['projectId'])
                ->whereNull('work_order_list_id')
                ->max('position_in_list') ?? 0;
            $positionInList = $maxPosition + 100;
        }

        WorkOrder::create([
            'team_id' => $team->id,
            'project_id' => $validated['projectId'],
            'work_order_list_id' => $listId,
            'position_in_list' => $positionInList,
            'assigned_to_id' => $validated['assignedToId'] ?? null,
            'created_by_id' => $user->id,
            'party_contact_id' => $project->party_id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => WorkOrderStatus::Draft,
            'priority' => Priority::from($validated['priority']),
            'due_date' => $validated['dueDate'] ?? null,
            'estimated_hours' => $validated['estimatedHours'] ?? 0,
            'acceptance_criteria' => $validated['acceptanceCriteria'] ?? [],
            'accountable_id' => $user->id, // Creator is initially accountable (RACI)
        ]);

        return back();
    }

    public function show(Request $request, WorkOrder $workOrder, WorkflowTransitionService $transitionService): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load(['project', 'assignedTo', 'createdBy', 'tasks', 'deliverables', 'documents', 'statusTransitions.user', 'accountable', 'responsible']);

        // Get communication thread and messages
        $thread = $workOrder->communicationThread;
        $messages = $thread ? $thread->messages()->with('author')->orderBy('created_at', 'desc')->get() : collect();

        // Get allowed transitions for current user
        $allowedTransitions = $this->getFormattedAllowedTransitions($workOrder, $request->user(), $transitionService);

        // Get rejection feedback if applicable
        $rejectionFeedback = $this->getRejectionFeedback($workOrder);

        return Inertia::render('work/work-orders/[id]', [
            'workOrder' => [
                'id' => (string) $workOrder->id,
                'title' => $workOrder->title,
                'description' => $workOrder->description,
                'projectId' => (string) $workOrder->project_id,
                'projectName' => $workOrder->project?->name ?? 'Unknown',
                'assignedToId' => $workOrder->assigned_to_id ? (string) $workOrder->assigned_to_id : null,
                'assignedToName' => $workOrder->assignedTo?->name ?? 'Unassigned',
                'status' => $workOrder->status->value,
                'priority' => $workOrder->priority->value,
                'dueDate' => $workOrder->due_date?->format('Y-m-d'),
                'estimatedHours' => (float) $workOrder->estimated_hours,
                'actualHours' => (float) $workOrder->actual_hours,
                'acceptanceCriteria' => $workOrder->acceptance_criteria ?? [],
                'sopAttached' => $workOrder->sop_attached,
                'sopName' => $workOrder->sop_name,
                'createdBy' => (string) $workOrder->created_by_id,
                'createdByName' => $workOrder->createdBy?->name ?? 'Unknown',
                'accountableId' => $workOrder->accountable_id,
                'accountableName' => $workOrder->accountable?->name,
                'responsibleId' => $workOrder->responsible_id,
                'responsibleName' => $workOrder->responsible?->name,
                'reviewerId' => $workOrder->reviewer_id ?? null,
                'consultedIds' => $workOrder->consulted_ids ?? [],
                'informedIds' => $workOrder->informed_ids ?? [],
            ],
            'tasks' => $workOrder->tasks()->ordered()->get()->map(fn (Task $task) => [
                'id' => (string) $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status->value,
                'dueDate' => $task->due_date?->format('Y-m-d'),
                'assignedToId' => $task->assigned_to_id ? (string) $task->assigned_to_id : null,
                'assignedToName' => $task->assignedTo?->name ?? 'Unassigned',
                'estimatedHours' => (float) $task->estimated_hours,
                'actualHours' => (float) $task->actual_hours,
                'checklistItems' => $task->checklist_items ?? [],
                'isBlocked' => $task->is_blocked,
                'positionInWorkOrder' => $task->position_in_work_order,
            ]),
            'deliverables' => $workOrder->deliverables->map(fn ($del) => [
                'id' => (string) $del->id,
                'title' => $del->title,
                'description' => $del->description,
                'type' => $del->type->value,
                'status' => $del->status->value,
                'version' => $del->version,
                'createdDate' => $del->created_date->format('Y-m-d'),
                'deliveredDate' => $del->delivered_date?->format('Y-m-d'),
                'fileUrl' => $del->file_url,
                'acceptanceCriteria' => $del->acceptance_criteria ?? [],
            ]),
            'documents' => $workOrder->documents->map(fn (Document $doc) => [
                'id' => (string) $doc->id,
                'name' => $doc->name,
                'type' => $doc->type->value,
                'fileUrl' => $doc->file_url,
                'fileSize' => $doc->file_size,
            ]),
            'communicationThread' => $thread ? [
                'id' => (string) $thread->id,
                'messageCount' => $thread->message_count,
            ] : null,
            'messages' => $messages->map(fn (Message $msg) => [
                'id' => (string) $msg->id,
                'authorId' => (string) $msg->author_id,
                'authorName' => $msg->author?->name ?? 'Unknown',
                'authorType' => $msg->author_type->value,
                'timestamp' => $msg->created_at->toIso8601String(),
                'content' => $msg->content,
                'type' => $msg->type->value,
            ]),
            'teamMembers' => $workOrder->project->team->users
                ->push($workOrder->project->team->owner)
                ->push($request->user())
                ->unique('id')
                ->filter()
                ->values()
                ->map(fn ($user) => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                ]),
            'statusTransitions' => $workOrder->statusTransitions->map(fn ($transition) => [
                'id' => $transition->id,
                'fromStatus' => $transition->from_status,
                'toStatus' => $transition->to_status,
                'user' => $transition->user ? [
                    'id' => $transition->user->id,
                    'name' => $transition->user->name,
                    'email' => $transition->user->email,
                ] : null,
                'createdAt' => $transition->created_at->toIso8601String(),
                'comment' => $transition->comment,
                'commentCategory' => null,
            ]),
            'allowedTransitions' => $allowedTransitions,
            'raciValue' => [
                'responsible_id' => $workOrder->responsible_id,
                'accountable_id' => $workOrder->accountable_id,
                'consulted_ids' => $workOrder->consulted_ids ?? [],
                'informed_ids' => $workOrder->informed_ids ?? [],
            ],
            'rejectionFeedback' => $rejectionFeedback,
        ]);
    }

    /**
     * Get formatted allowed transitions for the frontend.
     */
    private function getFormattedAllowedTransitions(WorkOrder $workOrder, $user, WorkflowTransitionService $transitionService): array
    {
        $transitions = $transitionService->getAvailableTransitions($workOrder, $user);

        $labels = [
            'draft' => 'Set as Draft',
            'active' => 'Start Work Order',
            'in_review' => 'Submit for Review',
            'approved' => 'Approve',
            'delivered' => 'Mark as Delivered',
            'blocked' => 'Mark as Blocked',
            'cancelled' => 'Cancel',
            'revision_requested' => 'Request Changes',
        ];

        $destructive = ['cancelled', 'revision_requested'];

        return collect($transitions)->map(fn ($status) => [
            'value' => $status,
            'label' => $labels[$status] ?? ucfirst(str_replace('_', ' ', $status)),
            'destructive' => in_array($status, $destructive),
        ])->values()->all();
    }

    /**
     * Get rejection feedback if the work order was recently rejected.
     */
    private function getRejectionFeedback(WorkOrder $workOrder): ?array
    {
        if ($workOrder->status !== WorkOrderStatus::Active) {
            return null;
        }

        $lastTransition = $workOrder->statusTransitions()
            ->where('to_status', 'revision_requested')
            ->with('user')
            ->orderByDesc('created_at')
            ->first();

        if (! $lastTransition || ! $lastTransition->comment) {
            return null;
        }

        return [
            'comment' => $lastTransition->comment,
            'user' => [
                'id' => $lastTransition->user?->id,
                'name' => $lastTransition->user?->name ?? 'Unknown',
                'email' => $lastTransition->user?->email ?? '',
            ],
            'createdAt' => $lastTransition->created_at->toIso8601String(),
        ];
    }

    public function update(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('update', $workOrder);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'assignedToId' => 'nullable|exists:users,id',
            'priority' => 'sometimes|required|string|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'acceptanceCriteria' => 'nullable|array',
        ]);

        $updateData = [];
        if (isset($validated['title'])) $updateData['title'] = $validated['title'];
        if (array_key_exists('description', $validated)) $updateData['description'] = $validated['description'];
        if (array_key_exists('assignedToId', $validated)) $updateData['assigned_to_id'] = $validated['assignedToId'];
        if (isset($validated['priority'])) $updateData['priority'] = Priority::from($validated['priority']);
        if (array_key_exists('due_date', $validated)) $updateData['due_date'] = $validated['due_date'];
        if (array_key_exists('estimated_hours', $validated)) $updateData['estimated_hours'] = $validated['estimated_hours'] ?? 0;
        if (isset($validated['acceptanceCriteria'])) $updateData['acceptance_criteria'] = $validated['acceptanceCriteria'];

        $workOrder->update($updateData);

        return back();
    }

    public function destroy(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('delete', $workOrder);

        $workOrder->delete();

        return redirect()->route('projects.show', $workOrder->project_id);
    }

    public function updateStatus(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('update', $workOrder);

        $validated = $request->validate([
            'status' => 'required|string|in:draft,active,in_review,approved,delivered',
        ]);

        $workOrder->update([
            'status' => WorkOrderStatus::from($validated['status']),
        ]);

        return back();
    }
}
