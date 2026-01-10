<?php

namespace App\Http\Controllers\Work;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
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

        $task->load(['workOrder', 'project', 'assignedTo', 'timeEntries.user', 'documents']);

        // Get active timer if any
        $activeTimer = $task->timeEntries()
            ->whereNotNull('started_at')
            ->whereNull('stopped_at')
            ->where('user_id', $request->user()->id)
            ->first();

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
}
