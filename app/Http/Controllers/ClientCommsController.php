<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CommunicationType;
use App\Http\Requests\DraftClientCommunicationRequest;
use App\Models\Message;
use App\Models\Project;
use App\Models\WorkOrder;
use App\Services\ClientCommsDraftService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for client communication drafting actions.
 *
 * Handles manual triggering of AI-drafted client communications
 * and provides preview functionality for draft messages.
 */
class ClientCommsController extends Controller
{
    public function __construct(
        private readonly ClientCommsDraftService $draftService,
    ) {}

    /**
     * Create a draft client communication for the specified entity.
     *
     * Accepts entity type (project or work_order), entity ID, communication type,
     * and optional notes to create an AI-drafted communication.
     */
    public function draftUpdate(DraftClientCommunicationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Resolve the entity
        $entity = $this->resolveEntity(
            $validated['entity_type'],
            (int) $validated['entity_id']
        );

        // Validate user has access to the entity
        $this->authorize('view', $entity);

        // Parse communication type
        $communicationType = CommunicationType::from($validated['communication_type']);

        // Create the draft
        $draft = $this->draftService->createDraft(
            $entity,
            $communicationType,
            $validated['notes'] ?? null
        );

        // Create the approval inbox item
        $this->draftService->createApprovalItem($draft, $entity);

        // Redirect to inbox with success message
        return redirect()
            ->route('inbox')
            ->with('success', 'Draft communication created and awaiting approval.');
    }

    /**
     * Preview a draft message.
     */
    public function preview(Message $message): Response
    {
        // Ensure message belongs to user's team
        $thread = $message->communicationThread;
        if ($thread === null) {
            abort(404, 'Draft not found');
        }

        $this->authorize('view', $thread->threadable);

        // Get the related entity
        $entity = $thread->threadable;
        $party = $entity instanceof Project
            ? $entity->party
            : ($entity instanceof WorkOrder ? $entity->project?->party : null);

        return Inertia::render('client-comms/preview', [
            'draft' => [
                'id' => (string) $message->id,
                'content' => $message->content,
                'communicationType' => $message->draft_metadata['communication_type'] ?? null,
                'confidence' => $message->draft_metadata['confidence'] ?? 'medium',
                'targetLanguage' => $message->draft_metadata['target_language'] ?? 'en',
                'createdAt' => $message->created_at->toIso8601String(),
                'draftStatus' => $message->draft_status?->value,
                'editedAt' => $message->edited_at?->toIso8601String(),
            ],
            'entity' => [
                'type' => class_basename($entity),
                'id' => (string) $entity->id,
                'name' => $entity instanceof Project ? $entity->name : $entity->title,
            ],
            'recipient' => $party !== null ? [
                'name' => $party->contact_name ?? $party->name,
                'email' => $party->contact_email,
                'preferredLanguage' => $party->preferred_language ?? 'en',
            ] : null,
        ]);
    }

    /**
     * Resolve the entity from type and ID.
     */
    private function resolveEntity(string $type, int $id): Project|WorkOrder
    {
        return match ($type) {
            'project' => Project::findOrFail($id),
            'work_order' => WorkOrder::findOrFail($id),
            default => throw new \InvalidArgumentException("Invalid entity type: {$type}"),
        };
    }
}
