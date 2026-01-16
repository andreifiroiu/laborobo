<?php

declare(strict_types=1);

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileUploadRequest;
use App\Http\Resources\DeliverableVersionResource;
use App\Models\Deliverable;
use App\Models\DeliverableVersion;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeliverableVersionController extends Controller
{
    public function __construct(
        private readonly FileUploadService $fileUploadService
    ) {}

    /**
     * List all versions for a deliverable with pagination.
     */
    public function index(Request $request, Deliverable $deliverable): AnonymousResourceCollection
    {
        $this->authorize('view', $deliverable);

        $versions = $deliverable->versions()
            ->latestFirst()
            ->with('uploadedBy')
            ->paginate(15);

        return DeliverableVersionResource::collection($versions);
    }

    /**
     * Upload a new version for a deliverable.
     */
    public function store(FileUploadRequest $request, Deliverable $deliverable): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $deliverable);

        $file = $request->file('file');
        $nextVersionNumber = $this->getNextVersionNumber($deliverable);

        $fileUrl = $this->fileUploadService->storeDeliverableVersion(
            $file,
            $deliverable,
            $nextVersionNumber
        );

        $version = DeliverableVersion::create([
            'deliverable_id' => $deliverable->id,
            'version_number' => $nextVersionNumber,
            'file_url' => $fileUrl,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'notes' => $request->validated('notes'),
            'uploaded_by_id' => $request->user()->id,
        ]);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            $version->load('uploadedBy');

            return (new DeliverableVersionResource($version))
                ->response()
                ->setStatusCode(201);
        }

        return back();
    }

    /**
     * Get a single version's details.
     */
    public function show(Request $request, Deliverable $deliverable, DeliverableVersion $version): DeliverableVersionResource
    {
        $this->authorize('view', $deliverable);
        $this->ensureVersionBelongsToDeliverable($deliverable, $version);

        $version->load('uploadedBy');

        return new DeliverableVersionResource($version);
    }

    /**
     * Restore a previous version as a new version.
     */
    public function restore(Request $request, Deliverable $deliverable, DeliverableVersion $version): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $deliverable);
        $this->ensureVersionBelongsToDeliverable($deliverable, $version);

        $nextVersionNumber = $this->getNextVersionNumber($deliverable);

        $restoredVersion = DeliverableVersion::create([
            'deliverable_id' => $deliverable->id,
            'version_number' => $nextVersionNumber,
            'file_url' => $version->file_url,
            'file_name' => $version->file_name,
            'file_size' => $version->file_size,
            'mime_type' => $version->mime_type,
            'notes' => "Restored from version {$version->version_number}",
            'uploaded_by_id' => $request->user()->id,
        ]);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            $restoredVersion->load('uploadedBy');

            return (new DeliverableVersionResource($restoredVersion))
                ->response()
                ->setStatusCode(201);
        }

        return back();
    }

    /**
     * Soft delete a version.
     */
    public function destroy(Request $request, Deliverable $deliverable, DeliverableVersion $version): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $deliverable);
        $this->ensureVersionBelongsToDeliverable($deliverable, $version);

        $version->delete();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(null, 204);
        }

        return back();
    }

    /**
     * Get the next version number for a deliverable.
     */
    private function getNextVersionNumber(Deliverable $deliverable): int
    {
        $maxVersion = $deliverable->versions()->max('version_number');

        return ($maxVersion ?? 0) + 1;
    }

    /**
     * Ensure the version belongs to the deliverable.
     */
    private function ensureVersionBelongsToDeliverable(Deliverable $deliverable, DeliverableVersion $version): void
    {
        if ($version->deliverable_id !== $deliverable->id) {
            abort(404, 'Version not found for this deliverable.');
        }
    }
}
