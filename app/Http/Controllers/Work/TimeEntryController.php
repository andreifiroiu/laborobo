<?php

namespace App\Http\Controllers\Work;

use App\Enums\TimeTrackingMode;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimeEntryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'taskId' => 'required|exists:tasks,id',
            'hours' => 'required|numeric|min:0.01|max:24',
            'date' => 'required|date',
            'note' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $team = $user->currentTeam;
        $task = Task::findOrFail($validated['taskId']);

        TimeEntry::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'task_id' => $validated['taskId'],
            'hours' => $validated['hours'],
            'date' => $validated['date'],
            'mode' => TimeTrackingMode::Manual,
            'note' => $validated['note'] ?? null,
        ]);

        // Update task actual hours
        $task->recalculateActualHours();

        return back();
    }

    public function startTimer(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $user = $request->user();

        // Stop any existing active timers for this user (regardless of task)
        $existingTimers = TimeEntry::where('user_id', $user->id)
            ->whereNotNull('started_at')
            ->whereNull('stopped_at')
            ->get();

        foreach ($existingTimers as $timer) {
            $timer->stopTimer();
        }

        TimeEntry::startTimer($task, $user);

        return back();
    }

    public function stopTimer(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $user = $request->user();

        $activeTimer = TimeEntry::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->whereNotNull('started_at')
            ->whereNull('stopped_at')
            ->first();

        if (!$activeTimer) {
            return back()->withErrors(['timer' => 'No active timer found for this task.']);
        }

        $activeTimer->stopTimer();

        return back();
    }
}
