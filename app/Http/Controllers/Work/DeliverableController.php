<?php

namespace App\Http\Controllers\Work;

use App\Enums\DeliverableStatus;
use App\Enums\DeliverableType;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\Deliverable;
use App\Models\Document;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DeliverableController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'workOrderId' => 'required|exists:work_orders,id',
            'type' => 'required|string|in:document,design,report,code,other',
            'fileUrl' => 'nullable|string|url',
            'acceptanceCriteria' => 'nullable|array',
        ]);

        $user = $request->user();
        $team = $user->currentTeam;
        $workOrder = WorkOrder::findOrFail($validated['workOrderId']);

        Deliverable::create([
            'team_id' => $team->id,
            'work_order_id' => $validated['workOrderId'],
            'project_id' => $workOrder->project_id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => DeliverableType::from($validated['type']),
            'status' => DeliverableStatus::Draft,
            'version' => '1.0',
            'created_date' => now(),
            'file_url' => $validated['fileUrl'] ?? null,
            'acceptance_criteria' => $validated['acceptanceCriteria'] ?? [],
        ]);

        return back();
    }

    public function show(Request $request, Deliverable $deliverable): Response
    {
        $this->authorize('view', $deliverable);

        $deliverable->load(['workOrder', 'project', 'documents']);

        return Inertia::render('work/deliverables/[id]', [
            'deliverable' => [
                'id' => (string) $deliverable->id,
                'title' => $deliverable->title,
                'description' => $deliverable->description,
                'workOrderId' => (string) $deliverable->work_order_id,
                'workOrderTitle' => $deliverable->workOrder?->title ?? 'Unknown',
                'projectId' => (string) $deliverable->project_id,
                'projectName' => $deliverable->project?->name ?? 'Unknown',
                'type' => $deliverable->type->value,
                'status' => $deliverable->status->value,
                'version' => $deliverable->version,
                'createdDate' => $deliverable->created_date->format('Y-m-d'),
                'deliveredDate' => $deliverable->delivered_date?->format('Y-m-d'),
                'fileUrl' => $deliverable->file_url,
                'acceptanceCriteria' => $deliverable->acceptance_criteria ?? [],
            ],
            'documents' => $deliverable->documents->map(fn (Document $doc) => [
                'id' => (string) $doc->id,
                'name' => $doc->name,
                'type' => $doc->type->value,
                'fileUrl' => $doc->file_url,
                'fileSize' => $doc->file_size,
                'uploadedAt' => $doc->created_at->format('Y-m-d H:i'),
            ]),
        ]);
    }

    public function update(Request $request, Deliverable $deliverable): RedirectResponse
    {
        $this->authorize('update', $deliverable);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|string|in:document,design,report,code,other',
            'status' => 'sometimes|required|string|in:draft,in_review,approved,delivered',
            'version' => 'sometimes|string|max:20',
            'fileUrl' => 'nullable|string|url',
            'acceptanceCriteria' => 'nullable|array',
        ]);

        $updateData = [];
        if (isset($validated['title'])) $updateData['title'] = $validated['title'];
        if (array_key_exists('description', $validated)) $updateData['description'] = $validated['description'];
        if (isset($validated['type'])) $updateData['type'] = DeliverableType::from($validated['type']);
        if (isset($validated['status'])) {
            $updateData['status'] = DeliverableStatus::from($validated['status']);
            // Set delivered date if status is delivered
            if ($validated['status'] === 'delivered' && !$deliverable->delivered_date) {
                $updateData['delivered_date'] = now();
            }
        }
        if (isset($validated['version'])) $updateData['version'] = $validated['version'];
        if (array_key_exists('fileUrl', $validated)) $updateData['file_url'] = $validated['fileUrl'];
        if (isset($validated['acceptanceCriteria'])) $updateData['acceptance_criteria'] = $validated['acceptanceCriteria'];

        $deliverable->update($updateData);

        return back();
    }

    public function destroy(Request $request, Deliverable $deliverable): RedirectResponse
    {
        $this->authorize('delete', $deliverable);

        $workOrderId = $deliverable->work_order_id;
        $deliverable->delete();

        return redirect()->route('work-orders.show', $workOrderId);
    }

    public function uploadFile(Request $request, Deliverable $deliverable): RedirectResponse
    {
        $this->authorize('update', $deliverable);

        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $user = $request->user();
        $file = $validated['file'];
        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        // Store file in deliverables directory
        $path = $file->store("deliverables/{$deliverable->id}", 'public');
        $fileUrl = Storage::disk('public')->url($path);

        // Use Artifact type for deliverable files
        $documentType = DocumentType::Artifact;

        // Create document record
        Document::create([
            'team_id' => $deliverable->team_id,
            'uploaded_by_id' => $user->id,
            'documentable_type' => Deliverable::class,
            'documentable_id' => $deliverable->id,
            'name' => $fileName,
            'type' => $documentType,
            'file_url' => $fileUrl,
            'file_size' => $this->formatFileSize($fileSize),
        ]);

        return back();
    }

    public function deleteFile(Request $request, Deliverable $deliverable, Document $document): RedirectResponse
    {
        $this->authorize('update', $deliverable);

        // Verify document belongs to this deliverable
        if ($document->documentable_type !== Deliverable::class || $document->documentable_id !== $deliverable->id) {
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
