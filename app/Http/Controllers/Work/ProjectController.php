<?php

namespace App\Http\Controllers\Work;

use App\Enums\DocumentType;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\CommunicationThread;
use App\Models\Deliverable;
use App\Models\Document;
use App\Models\Message;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'partyId' => 'required|exists:parties,id',
            'startDate' => 'required|date',
            'targetEndDate' => 'nullable|date|after_or_equal:startDate',
            'budgetHours' => 'nullable|numeric|min:0',
            'tags' => 'nullable|array',
        ]);

        $user = $request->user();
        $team = $user->currentTeam;

        Project::create([
            'team_id' => $team->id,
            'party_id' => $validated['partyId'],
            'owner_id' => $user->id,
            'accountable_id' => $user->id, // Owner is initially accountable (RACI)
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => ProjectStatus::Active,
            'start_date' => $validated['startDate'],
            'target_end_date' => $validated['targetEndDate'] ?? null,
            'budget_hours' => $validated['budgetHours'] ?? null,
            'tags' => $validated['tags'] ?? [],
        ]);

        return back();
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $project->load(['party', 'owner', 'workOrders.tasks', 'workOrders.deliverables', 'documents']);

        // Get communication thread and messages
        $thread = $project->communicationThread;
        $messages = $thread ? $thread->messages()->with('author')->orderBy('created_at', 'desc')->get() : collect();

        return Inertia::render('work/projects/[id]', [
            'project' => [
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
            ],
            'workOrders' => $project->workOrders->map(fn (WorkOrder $wo) => [
                'id' => (string) $wo->id,
                'title' => $wo->title,
                'status' => $wo->status->value,
                'priority' => $wo->priority->value,
                'dueDate' => $wo->due_date->format('Y-m-d'),
                'assignedToName' => $wo->assignedTo?->name ?? 'Unassigned',
                'tasksCount' => $wo->tasks->count(),
                'completedTasksCount' => $wo->tasks->where('status', 'done')->count(),
            ]),
            'documents' => $project->documents->map(fn (Document $doc) => [
                'id' => (string) $doc->id,
                'name' => $doc->name,
                'type' => $doc->type->value,
                'fileUrl' => $doc->file_url,
                'fileSize' => $doc->file_size,
                'uploadedDate' => $doc->created_at->format('Y-m-d'),
            ]),
            'communicationThread' => $thread ? [
                'id' => (string) $thread->id,
                'messageCount' => $thread->message_count,
                'lastActivity' => $thread->last_activity?->toIso8601String(),
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
            'parties' => Party::forTeam($project->team_id)->get()->map(fn (Party $p) => [
                'id' => (string) $p->id,
                'name' => $p->name,
            ]),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'partyId' => 'sometimes|required|exists:parties,id',
            'status' => 'sometimes|required|string|in:active,on_hold,completed,archived',
            'startDate' => 'sometimes|required|date',
            'targetEndDate' => 'nullable|date|after_or_equal:startDate',
            'budgetHours' => 'nullable|numeric|min:0',
            'tags' => 'nullable|array',
        ]);

        $updateData = [];
        if (isset($validated['name'])) $updateData['name'] = $validated['name'];
        if (array_key_exists('description', $validated)) $updateData['description'] = $validated['description'];
        if (isset($validated['partyId'])) $updateData['party_id'] = $validated['partyId'];
        if (isset($validated['status'])) $updateData['status'] = ProjectStatus::from($validated['status']);
        if (isset($validated['startDate'])) $updateData['start_date'] = $validated['startDate'];
        if (array_key_exists('targetEndDate', $validated)) $updateData['target_end_date'] = $validated['targetEndDate'];
        if (array_key_exists('budgetHours', $validated)) $updateData['budget_hours'] = $validated['budgetHours'];
        if (isset($validated['tags'])) $updateData['tags'] = $validated['tags'];

        $project->update($updateData);

        return back();
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('work');
    }

    public function archive(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->update(['status' => ProjectStatus::Archived]);

        return back();
    }

    public function restore(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->update(['status' => ProjectStatus::Active]);

        return back();
    }

    public function uploadFile(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $user = $request->user();
        $file = $validated['file'];
        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        // Store file in projects directory
        $path = $file->store("projects/{$project->id}", 'public');
        $fileUrl = Storage::disk('public')->url($path);

        // Create document record
        Document::create([
            'team_id' => $project->team_id,
            'uploaded_by_id' => $user->id,
            'documentable_type' => Project::class,
            'documentable_id' => $project->id,
            'name' => $fileName,
            'type' => DocumentType::Reference,
            'file_url' => $fileUrl,
            'file_size' => $this->formatFileSize($fileSize),
        ]);

        return back();
    }

    public function deleteFile(Request $request, Project $project, Document $document): RedirectResponse
    {
        $this->authorize('update', $project);

        // Verify document belongs to this project
        if ($document->documentable_type !== Project::class || $document->documentable_id !== $project->id) {
            abort(403);
        }

        // Delete file from storage
        $path = str_replace(Storage::disk('public')->url(''), '', $document->file_url);
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $document->delete();

        return back();
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = $bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 1) . ' ' . $units[$unitIndex];
    }
}
