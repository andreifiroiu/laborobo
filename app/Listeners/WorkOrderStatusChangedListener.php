<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\CommunicationType;
use App\Enums\WorkOrderStatus;
use App\Events\WorkOrderStatusChanged;
use App\Models\GlobalAISettings;
use App\Services\ClientCommsDraftService;
use Illuminate\Support\Facades\Log;

/**
 * Listens for WorkOrderStatusChanged events and creates draft client
 * communications when work orders transition to specific states.
 *
 * Creates draft suggestions for:
 * - Transitions to InReview status (status update)
 * - Transitions to Delivered status (status update)
 */
class WorkOrderStatusChangedListener
{
    public function __construct(
        private readonly ClientCommsDraftService $draftService,
    ) {}

    /**
     * Handle the WorkOrderStatusChanged event.
     *
     * Checks if client comms auto-draft is enabled and creates a draft
     * suggestion for relevant status transitions.
     */
    public function handle(WorkOrderStatusChanged $event): void
    {
        $workOrder = $event->workOrder;
        $team = $workOrder->team;

        if ($team === null) {
            Log::warning('WorkOrderStatusChanged: No team found for work order', [
                'work_order_id' => $workOrder->id,
            ]);

            return;
        }

        // Get team settings
        $settings = GlobalAISettings::forTeam($team);

        // Check if auto-draft is enabled
        if (! $this->isAutoDraftEnabled($settings)) {
            Log::debug('Client comms auto-draft is disabled for team', [
                'team_id' => $team->id,
                'work_order_id' => $workOrder->id,
            ]);

            return;
        }

        // Check if this is a relevant status transition
        if (! $this->isRelevantTransition($event->toStatus)) {
            Log::debug('Status transition not relevant for client comms draft', [
                'work_order_id' => $workOrder->id,
                'from_status' => $event->fromStatus->value,
                'to_status' => $event->toStatus->value,
            ]);

            return;
        }

        Log::info('Creating client comms draft for work order status change', [
            'work_order_id' => $workOrder->id,
            'from_status' => $event->fromStatus->value,
            'to_status' => $event->toStatus->value,
        ]);

        // Determine communication type based on status transition
        $communicationType = $this->determineCommunicationType($event->toStatus);

        // Create the draft
        $draft = $this->draftService->createDraft(
            $workOrder,
            $communicationType,
            $this->buildEventNotes($event)
        );

        // Add event-driven source metadata
        $draft->update([
            'draft_metadata' => array_merge($draft->draft_metadata ?? [], [
                'source_type' => 'event_driven',
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
        // Check the client_comms_auto_draft setting if it exists, otherwise default to false
        return (bool) ($settings->client_comms_auto_draft ?? false);
    }

    /**
     * Check if the status transition is relevant for client communication drafts.
     */
    private function isRelevantTransition(WorkOrderStatus $toStatus): bool
    {
        return in_array($toStatus, [
            WorkOrderStatus::InReview,
            WorkOrderStatus::Delivered,
        ], true);
    }

    /**
     * Determine the communication type based on the new status.
     */
    private function determineCommunicationType(WorkOrderStatus $toStatus): CommunicationType
    {
        return match ($toStatus) {
            WorkOrderStatus::InReview => CommunicationType::StatusUpdate,
            WorkOrderStatus::Delivered => CommunicationType::StatusUpdate,
            default => CommunicationType::StatusUpdate,
        };
    }

    /**
     * Build notes describing the event context.
     */
    private function buildEventNotes(WorkOrderStatusChanged $event): string
    {
        $statusLabel = $event->toStatus->label();

        return "Automatically triggered by work order status change to {$statusLabel}.";
    }
}
