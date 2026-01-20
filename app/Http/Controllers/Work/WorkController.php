<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Controller;
use App\Models\CommunicationThread;
use App\Models\Deliverable;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $team = $user->currentTeam;

        if (!$team) {
            return Inertia::render('work/index', [
                'projects' => [],
                'workOrders' => [],
                'tasks' => [],
                'deliverables' => [],
                'parties' => [],
                'teamMembers' => [],
                'communicationThreads' => [],
                'currentView' => 'all_projects',
                'currentUserId' => (string) $user->id,
            ]);
        }

        // Get user's preferred view
        $currentView = UserPreference::get($user, 'work_view', 'all_projects');

        return Inertia::render('work/index', [
            'projects' => $this->getProjects($team),
            'workOrders' => $this->getWorkOrders($team),
            'tasks' => $this->getTasks($team),
            'deliverables' => $this->getDeliverables($team),
            'parties' => $this->getParties($team),
            'teamMembers' => $this->getTeamMembers($team),
            'communicationThreads' => $this->getCommunicationThreads($team),
            'currentView' => $currentView,
            'currentUserId' => (string) $user->id,
        ]);
    }

    public function updatePreference(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'nullable|string',
        ]);

        UserPreference::set($request->user(), $request->key, $request->value);

        return back();
    }

    private function getProjects(Team $team): array
    {
        return Project::forTeam($team->id)
            ->with([
                'party',
                'owner',
                'workOrderLists.workOrders.tasks',
                'workOrderLists.workOrders.assignedTo',
            ])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn (Project $project) => [
                'id' => (string) $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'partyId' => (string) $project->party_id,
                'partyName' => $project->party?->name ?? 'Unknown',
                'ownerId' => (string) $project->owner_id,
                'ownerName' => $project->owner?->name ?? 'Unknown',
                'status' => $project->status->value,
                'startDate' => $project->start_date->format('Y-m-d'),
                'targetEndDate' => $project->target_end_date?->format('Y-m-d'),
                'budgetHours' => (float) $project->budget_hours,
                'actualHours' => (float) $project->actual_hours,
                'progress' => $project->progress,
                'tags' => $project->tags ?? [],
                'workOrderLists' => $project->workOrderLists->map(fn ($list) => [
                    'id' => (string) $list->id,
                    'name' => $list->name,
                    'color' => $list->color,
                    'position' => $list->position,
                    'workOrders' => $list->workOrders->map(fn ($wo) => [
                        'id' => (string) $wo->id,
                        'title' => $wo->title,
                        'status' => $wo->status->value,
                        'priority' => $wo->priority->value,
                        'dueDate' => $wo->due_date?->format('Y-m-d'),
                        'assignedToName' => $wo->assignedTo?->name ?? 'Unassigned',
                        'tasksCount' => $wo->tasks->count(),
                        'completedTasksCount' => $wo->tasks->where('status', 'done')->count(),
                        'positionInList' => $wo->position_in_list,
                    ])->all(),
                ])->all(),
                'ungroupedWorkOrders' => $project->ungroupedWorkOrders()
                    ->with(['tasks', 'assignedTo'])
                    ->get()
                    ->map(fn ($wo) => [
                        'id' => (string) $wo->id,
                        'title' => $wo->title,
                        'status' => $wo->status->value,
                        'priority' => $wo->priority->value,
                        'dueDate' => $wo->due_date?->format('Y-m-d'),
                        'assignedToName' => $wo->assignedTo?->name ?? 'Unassigned',
                        'tasksCount' => $wo->tasks->count(),
                        'completedTasksCount' => $wo->tasks->where('status', 'done')->count(),
                        'positionInList' => $wo->position_in_list,
                    ])->all(),
            ])
            ->all();
    }

    private function getWorkOrders(Team $team): array
    {
        return WorkOrder::forTeam($team->id)
            ->with(['project', 'assignedTo', 'createdBy'])
            ->orderBy('due_date')
            ->get()
            ->map(fn (WorkOrder $wo) => [
                'id' => (string) $wo->id,
                'title' => $wo->title,
                'description' => $wo->description,
                'projectId' => (string) $wo->project_id,
                'projectName' => $wo->project?->name ?? 'Unknown',
                'assignedToId' => $wo->assigned_to_id ? (string) $wo->assigned_to_id : null,
                'assignedToName' => $wo->assignedTo?->name ?? 'Unassigned',
                'status' => $wo->status->value,
                'priority' => $wo->priority->value,
                'dueDate' => $wo->due_date->format('Y-m-d'),
                'estimatedHours' => (float) $wo->estimated_hours,
                'actualHours' => (float) $wo->actual_hours,
                'acceptanceCriteria' => $wo->acceptance_criteria ?? [],
                'sopAttached' => $wo->sop_attached,
                'sopName' => $wo->sop_name,
                'partyContactId' => $wo->party_contact_id ? (string) $wo->party_contact_id : null,
                'createdBy' => (string) $wo->created_by_id,
                'createdByName' => $wo->createdBy?->name ?? 'Unknown',
            ])
            ->all();
    }

    private function getTasks(Team $team): array
    {
        return Task::forTeam($team->id)
            ->with(['workOrder', 'project', 'assignedTo'])
            ->orderBy('due_date')
            ->get()
            ->map(fn (Task $task) => [
                'id' => (string) $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'workOrderId' => (string) $task->work_order_id,
                'workOrderTitle' => $task->workOrder?->title ?? 'Unknown',
                'projectId' => (string) $task->project_id,
                'assignedToId' => $task->assigned_to_id ? (string) $task->assigned_to_id : null,
                'assignedToName' => $task->assignedTo?->name ?? 'Unassigned',
                'status' => $task->status->value,
                'dueDate' => $task->due_date->format('Y-m-d'),
                'estimatedHours' => (float) $task->estimated_hours,
                'actualHours' => (float) $task->actual_hours,
                'checklistItems' => $task->checklist_items ?? [],
                'dependencies' => $task->dependencies ?? [],
                'isBlocked' => $task->is_blocked,
            ])
            ->all();
    }

    private function getDeliverables(Team $team): array
    {
        return Deliverable::forTeam($team->id)
            ->with(['workOrder', 'project'])
            ->orderBy('created_date', 'desc')
            ->get()
            ->map(fn (Deliverable $del) => [
                'id' => (string) $del->id,
                'title' => $del->title,
                'description' => $del->description,
                'workOrderId' => (string) $del->work_order_id,
                'workOrderTitle' => $del->workOrder?->title ?? 'Unknown',
                'projectId' => (string) $del->project_id,
                'type' => $del->type->value,
                'status' => $del->status->value,
                'version' => $del->version,
                'createdDate' => $del->created_date->format('Y-m-d'),
                'deliveredDate' => $del->delivered_date?->format('Y-m-d'),
                'fileUrl' => $del->file_url,
                'acceptanceCriteria' => $del->acceptance_criteria ?? [],
            ])
            ->all();
    }

    private function getParties(Team $team): array
    {
        return Party::forTeam($team->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Party $party) => [
                'id' => (string) $party->id,
                'name' => $party->name,
                'type' => $party->type->value,
                'contactName' => $party->contact_name,
                'contactEmail' => $party->contact_email,
            ])
            ->all();
    }

    private function getTeamMembers(Team $team): array
    {
        return $team->users()
            ->get()
            ->map(fn (User $user) => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'type' => 'human',
                'role' => 'Team Member',
                'avatarUrl' => $user->avatar,
                'skills' => [],
            ])
            ->all();
    }

    private function getCommunicationThreads(Team $team): array
    {
        return CommunicationThread::forTeam($team->id)
            ->with('threadable')
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(fn (CommunicationThread $thread) => [
                'id' => (string) $thread->id,
                'workItemType' => $thread->threadable_type === Project::class ? 'project' : 'workOrder',
                'workItemId' => (string) $thread->threadable_id,
                'workItemTitle' => $thread->threadable?->name ?? $thread->threadable?->title ?? 'Unknown',
                'messageCount' => $thread->message_count,
                'lastActivity' => $thread->last_activity?->toIso8601String(),
            ])
            ->all();
    }
}
