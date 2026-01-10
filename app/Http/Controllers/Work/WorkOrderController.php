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
            'dueDate' => 'required|date',
            'estimatedHours' => 'nullable|numeric|min:0',
            'acceptanceCriteria' => 'nullable|array',
        ]);

        $user = $request->user();
        $team = $user->currentTeam;
        $project = Project::findOrFail($validated['projectId']);

        WorkOrder::create([
            'team_id' => $team->id,
            'project_id' => $validated['projectId'],
            'assigned_to_id' => $validated['assignedToId'] ?? null,
            'created_by_id' => $user->id,
            'party_contact_id' => $project->party_id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => WorkOrderStatus::Draft,
            'priority' => Priority::from($validated['priority']),
            'due_date' => $validated['dueDate'],
            'estimated_hours' => $validated['estimatedHours'] ?? 0,
            'acceptance_criteria' => $validated['acceptanceCriteria'] ?? [],
        ]);

        return back();
    }

    public function show(Request $request, WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load(['project', 'assignedTo', 'createdBy', 'tasks', 'deliverables', 'documents']);

        // Get communication thread and messages
        $thread = $workOrder->communicationThread;
        $messages = $thread ? $thread->messages()->with('author')->orderBy('created_at', 'desc')->get() : collect();

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
                'dueDate' => $workOrder->due_date->format('Y-m-d'),
                'estimatedHours' => (float) $workOrder->estimated_hours,
                'actualHours' => (float) $workOrder->actual_hours,
                'acceptanceCriteria' => $workOrder->acceptance_criteria ?? [],
                'sopAttached' => $workOrder->sop_attached,
                'sopName' => $workOrder->sop_name,
                'createdBy' => (string) $workOrder->created_by_id,
                'createdByName' => $workOrder->createdBy?->name ?? 'Unknown',
            ],
            'tasks' => $workOrder->tasks->map(fn (Task $task) => [
                'id' => (string) $task->id,
                'title' => $task->title,
                'status' => $task->status->value,
                'dueDate' => $task->due_date->format('Y-m-d'),
                'assignedToName' => $task->assignedTo?->name ?? 'Unassigned',
                'estimatedHours' => (float) $task->estimated_hours,
                'actualHours' => (float) $task->actual_hours,
                'checklistItems' => $task->checklist_items ?? [],
                'isBlocked' => $task->is_blocked,
            ]),
            'deliverables' => $workOrder->deliverables->map(fn ($del) => [
                'id' => (string) $del->id,
                'title' => $del->title,
                'type' => $del->type->value,
                'status' => $del->status->value,
                'version' => $del->version,
                'fileUrl' => $del->file_url,
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
            'teamMembers' => $workOrder->project->team->users->map(fn ($user) => [
                'id' => (string) $user->id,
                'name' => $user->name,
            ]),
        ]);
    }

    public function update(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('update', $workOrder);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'assignedToId' => 'nullable|exists:users,id',
            'priority' => 'sometimes|required|string|in:low,medium,high,urgent',
            'dueDate' => 'sometimes|required|date',
            'estimatedHours' => 'nullable|numeric|min:0',
            'acceptanceCriteria' => 'nullable|array',
        ]);

        $updateData = [];
        if (isset($validated['title'])) $updateData['title'] = $validated['title'];
        if (array_key_exists('description', $validated)) $updateData['description'] = $validated['description'];
        if (array_key_exists('assignedToId', $validated)) $updateData['assigned_to_id'] = $validated['assignedToId'];
        if (isset($validated['priority'])) $updateData['priority'] = Priority::from($validated['priority']);
        if (isset($validated['dueDate'])) $updateData['due_date'] = $validated['dueDate'];
        if (array_key_exists('estimatedHours', $validated)) $updateData['estimated_hours'] = $validated['estimatedHours'] ?? 0;
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
