<?php

namespace App\Http\Controllers;

use App\Models\InboxItem;
use App\Models\Project;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $team = $user->currentTeam;

        if (!$team) {
            return Inertia::render('inbox/index', [
                'inboxItems' => [],
                'teamMembers' => [],
                'projects' => [],
                'workOrders' => [],
            ]);
        }

        // Get inbox items with relationships
        $inboxItems = InboxItem::forTeam($team->id)
            ->with(['relatedWorkOrder', 'relatedProject'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (InboxItem $item) => [
                'id' => (string) $item->id,
                'type' => $item->type->value,
                'title' => $item->title,
                'contentPreview' => $item->content_preview,
                'fullContent' => $item->full_content,
                'sourceId' => $item->source_id,
                'sourceName' => $item->source_name,
                'sourceType' => $item->source_type->value,
                'relatedWorkOrderId' => $item->related_work_order_id ? (string) $item->related_work_order_id : null,
                'relatedWorkOrderTitle' => $item->related_work_order_title,
                'relatedProjectId' => $item->related_project_id ? (string) $item->related_project_id : null,
                'relatedProjectName' => $item->related_project_name,
                'urgency' => $item->urgency->value,
                'aiConfidence' => $item->ai_confidence?->value,
                'qaValidation' => $item->qa_validation?->value,
                'createdAt' => $item->created_at->toISOString(),
                'waitingHours' => $item->waitingHours,
            ])
            ->all();

        // Get team members for context
        $teamMembers = $team->allUsers()->map(fn ($user) => [
            'id' => (string) $user->id,
            'name' => $user->name,
            'type' => 'human',
            'role' => $user->role ?? 'Team Member',
            'avatarUrl' => $user->profile_photo_url,
            'skills' => [],
        ])->all();

        // Get projects and work orders for context
        $projects = Project::forTeam($team->id)->get()->map(fn ($project) => [
            'id' => (string) $project->id,
            'name' => $project->name,
            'description' => $project->description,
        ])->all();

        $workOrders = WorkOrder::forTeam($team->id)->get()->map(fn ($wo) => [
            'id' => (string) $wo->id,
            'title' => $wo->title,
            'projectId' => (string) $wo->project_id,
        ])->all();

        return Inertia::render('inbox/index', [
            'inboxItems' => $inboxItems,
            'teamMembers' => $teamMembers,
            'projects' => $projects,
            'workOrders' => $workOrders,
        ]);
    }

    public function approve(Request $request, InboxItem $inboxItem)
    {
        $this->authorize('update', $inboxItem);

        // Archive the item (soft delete)
        $inboxItem->delete();

        return back()->with('success', 'Item approved and archived.');
    }

    public function reject(Request $request, InboxItem $inboxItem)
    {
        $this->authorize('update', $inboxItem);

        $validated = $request->validate([
            'feedback' => 'required|string|max:1000',
        ]);

        // TODO: Send feedback to the source (agent or user)
        // For now, just archive the item
        $inboxItem->delete();

        return back()->with('success', 'Item rejected with feedback.');
    }

    public function defer(Request $request, InboxItem $inboxItem)
    {
        $this->authorize('update', $inboxItem);

        // Defer updates the timestamp to push it to the bottom
        $inboxItem->touch();

        return back()->with('success', 'Item deferred.');
    }

    public function archive(Request $request, InboxItem $inboxItem)
    {
        $this->authorize('delete', $inboxItem);

        $inboxItem->delete();

        return back()->with('success', 'Item archived.');
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'itemIds' => 'required|array',
            'itemIds.*' => 'exists:inbox_items,id',
            'action' => 'required|in:approve,defer,archive',
        ]);

        $team = $request->user()->currentTeam;
        $items = InboxItem::forTeam($team->id)
            ->whereIn('id', $validated['itemIds'])
            ->get();

        foreach ($items as $item) {
            $this->authorize('update', $item);

            switch ($validated['action']) {
                case 'approve':
                case 'archive':
                    $item->delete();
                    break;
                case 'defer':
                    $item->touch();
                    break;
            }
        }

        return back()->with('success', ucfirst($validated['action']) . ' action completed.');
    }
}
