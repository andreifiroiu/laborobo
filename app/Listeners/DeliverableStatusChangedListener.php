<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\CommunicationType;
use App\Enums\DeliverableStatus;
use App\Events\DeliverableStatusChanged;
use App\Models\GlobalAISettings;
use App\Services\ClientCommsDraftService;
use Illuminate\Support\Facades\Log;

/**
 * Listens for DeliverableStatusChanged events and creates draft client
 * communications when deliverables transition to specific states.
 *
 * Creates draft suggestions for:
 * - Transitions to Approved status (ready for client review)
 * - Transitions to Delivered status (deliverable sent to client)
 */
class DeliverableStatusChangedListener
{
    public function __construct(
        private readonly ClientCommsDraftService $draftService,
    ) {}

    /**
     * Handle the DeliverableStatusChanged event.
     *
     * Checks if client comms auto-draft is enabled and creates a draft
     * suggestion for relevant status transitions.
     */
    public function handle(DeliverableStatusChanged $event): void
    {
        $deliverable = $event->deliverable;
        $workOrder = $deliverable->workOrder;

        if ($workOrder === null) {
            Log::warning('DeliverableStatusChanged: No work order found for deliverable', [
                'deliverable_id' => $deliverable->id,
            ]);

            return;
        }

        $team = $workOrder->team;

        if ($team === null) {
            Log::warning('DeliverableStatusChanged: No team found for deliverable', [
                'deliverable_id' => $deliverable->id,
            ]);

            return;
        }

        // Get team settings
        $settings = GlobalAISettings::forTeam($team);

        // Check if auto-draft is enabled
        if (! $this->isAutoDraftEnabled($settings)) {
            Log::debug('Client comms auto-draft is disabled for team', [
                'team_id' => $team->id,
                'deliverable_id' => $deliverable->id,
            ]);

            return;
        }

        // Check if this is a relevant status transition
        if (! $this->isRelevantTransition($event->toStatus)) {
            Log::debug('Status transition not relevant for client comms draft', [
                'deliverable_id' => $deliverable->id,
                'from_status' => $event->fromStatus->value,
                'to_status' => $event->toStatus->value,
            ]);

            return;
        }

        Log::info('Creating client comms draft for deliverable status change', [
            'deliverable_id' => $deliverable->id,
            'work_order_id' => $workOrder->id,
            'from_status' => $event->fromStatus->value,
            'to_status' => $event->toStatus->value,
        ]);

        // Create the draft (using work order as the entity context)
        $draft = $this->draftService->createDraft(
            $workOrder,
            CommunicationType::DeliverableNotification,
            $this->buildEventNotes($event, $deliverable)
        );

        // Add event-driven source metadata with deliverable context
        $draft->update([
            'draft_metadata' => array_merge($draft->draft_metadata ?? [], [
                'source_type' => 'event_driven',
                'deliverable_id' => $deliverable->id,
                'deliverable_title' => $deliverable->title,
                'event_context' => [
                    'from_status' => $event->fromStatus->value,
                    'to_status' => $event->toStatus->value,
                    'triggered_by' => $event->user?->id,
                    'triggered_at' => now()->toIso8601String(),
                ],
            ]),
        ]);

        // Create approval inbox item
        $this->draftService->createApprovalItem($draft, $workOrder);
    }

    /**
     * Check if auto-draft is enabled in team settings.
     */
    private function isAutoDraftEnabled(GlobalAISettings $settings): bool
    {
        return (bool) ($settings->client_comms_auto_draft ?? false);
    }

    /**
     * Check if the status transition is relevant for client communication drafts.
     */
    private function isRelevantTransition(DeliverableStatus $toStatus): bool
    {
        return in_array($toStatus, [
            DeliverableStatus::Approved,
            DeliverableStatus::Delivered,
        ], true);
    }

    /**
     * Build notes describing the event context.
     */
    private function buildEventNotes(
        DeliverableStatusChanged $event,
        \App\Models\Deliverable $deliverable,
    ): string {
        $statusLabel = $event->toStatus->label();

        return "Deliverable '{$deliverable->title}' is now {$statusLabel}. Ready to notify the client.";
    }
}
