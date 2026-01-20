<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InboxItemType;
use App\Enums\SourceType;
use App\Enums\Urgency;
use App\Models\AgentWorkflowState;
use App\Models\InboxItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling agent approval requests.
 *
 * Creates InboxItems for agent actions requiring human approval,
 * and handles approval/rejection with workflow state updates.
 */
class AgentApprovalService
{
    public function __construct(
        private readonly AgentOrchestrator $orchestrator,
    ) {}

    /**
     * Request human approval for an agent action.
     *
     * Creates an InboxItem for the approval request and pauses the workflow.
     *
     * @param  AgentWorkflowState  $state  The workflow state requiring approval
     * @param  string  $actionDescription  Description of the action requiring approval
     * @param  Urgency  $urgency  The urgency level of the approval request
     * @return InboxItem The created inbox item
     */
    public function requestApproval(
        AgentWorkflowState $state,
        string $actionDescription,
        Urgency $urgency = Urgency::Normal,
    ): InboxItem {
        return DB::transaction(function () use ($state, $actionDescription, $urgency) {
            // Pause the workflow
            $this->orchestrator->pause($state, 'Awaiting human approval');

            $agent = $state->agent;
            $team = $state->team;

            // Build the inbox item content
            $title = 'Agent action requires approval: ' . $this->truncateString($actionDescription, 50);
            $contentPreview = $this->buildContentPreview($state, $actionDescription);
            $fullContent = $this->buildFullContent($state, $actionDescription);

            // Create the inbox item
            $inboxItem = InboxItem::create([
                'team_id' => $team->id,
                'type' => InboxItemType::Approval,
                'title' => $title,
                'content_preview' => $contentPreview,
                'full_content' => $fullContent,
                'source_id' => 'agent-' . ($agent?->id ?? 'unknown'),
                'source_name' => $agent?->name ?? 'Unknown Agent',
                'source_type' => SourceType::AIAgent,
                'approvable_type' => AgentWorkflowState::class,
                'approvable_id' => $state->id,
                'urgency' => $urgency,
            ]);

            // Update the workflow state with the inbox item reference
            $stateData = $state->state_data ?? [];
            $stateData['inbox_item_id'] = $inboxItem->id;
            $stateData['approval_requested_at'] = now()->toIso8601String();
            $state->update(['state_data' => $stateData]);

            Log::info('Agent approval requested', [
                'workflow_state_id' => $state->id,
                'inbox_item_id' => $inboxItem->id,
                'agent_id' => $agent?->id,
                'action' => $actionDescription,
            ]);

            return $inboxItem;
        });
    }

    /**
     * Handle approval of an agent action.
     *
     * Resumes the workflow and marks the inbox item as approved.
     *
     * @param  InboxItem  $item  The inbox item being approved
     * @param  User  $approver  The user approving the action
     */
    public function handleApproval(InboxItem $item, User $approver): void
    {
        DB::transaction(function () use ($item, $approver) {
            // Get the workflow state
            $state = $this->getWorkflowStateFromInboxItem($item);

            if ($state !== null) {
                // Resume the workflow with approval data
                $this->orchestrator->resume($state, [
                    'approved' => true,
                    'approver_id' => $approver->id,
                    'approver_name' => $approver->name,
                    'approved_at' => now()->toIso8601String(),
                ]);
            }

            // Mark the inbox item as approved
            $item->markAsApproved();

            Log::info('Agent action approved', [
                'inbox_item_id' => $item->id,
                'workflow_state_id' => $state?->id,
                'approver_id' => $approver->id,
            ]);
        });
    }

    /**
     * Handle rejection of an agent action.
     *
     * Updates the workflow state with rejection data and marks the inbox item as rejected.
     *
     * @param  InboxItem  $item  The inbox item being rejected
     * @param  User  $rejector  The user rejecting the action
     * @param  string  $reason  The reason for rejection
     */
    public function handleRejection(InboxItem $item, User $rejector, string $reason): void
    {
        DB::transaction(function () use ($item, $rejector, $reason) {
            // Get the workflow state
            $state = $this->getWorkflowStateFromInboxItem($item);

            if ($state !== null) {
                // Update the workflow state with rejection data
                $stateData = $state->state_data ?? [];
                $stateData['rejected'] = true;
                $stateData['rejection_reason'] = $reason;
                $stateData['rejected_by'] = $rejector->id;
                $stateData['rejected_at'] = now()->toIso8601String();

                $state->update([
                    'state_data' => $stateData,
                ]);
            }

            // Mark the inbox item as rejected
            $item->markAsRejected();

            Log::info('Agent action rejected', [
                'inbox_item_id' => $item->id,
                'workflow_state_id' => $state?->id,
                'rejector_id' => $rejector->id,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Find a pending approval inbox item for a workflow state.
     */
    public function findPendingApproval(AgentWorkflowState $state): ?InboxItem
    {
        return InboxItem::findPendingApprovalFor($state);
    }

    /**
     * Check if a workflow state has a pending approval.
     */
    public function hasPendingApproval(AgentWorkflowState $state): bool
    {
        return $this->findPendingApproval($state) !== null;
    }

    /**
     * Get the workflow state from an inbox item.
     */
    private function getWorkflowStateFromInboxItem(InboxItem $item): ?AgentWorkflowState
    {
        if ($item->approvable_type !== AgentWorkflowState::class) {
            return null;
        }

        return AgentWorkflowState::find($item->approvable_id);
    }

    /**
     * Build the content preview for the inbox item.
     */
    private function buildContentPreview(AgentWorkflowState $state, string $actionDescription): string
    {
        $agent = $state->agent;
        $agentName = $agent?->name ?? 'Unknown Agent';

        return "{$agentName} requests approval: {$actionDescription}";
    }

    /**
     * Build the full content for the inbox item.
     */
    private function buildFullContent(AgentWorkflowState $state, string $actionDescription): string
    {
        $agent = $state->agent;
        $agentName = $agent?->name ?? 'Unknown Agent';
        $workflowClass = class_basename($state->workflow_class);

        $content = "Agent: {$agentName}\n";
        $content .= "Workflow: {$workflowClass}\n";
        $content .= "Current Step: {$state->current_node}\n\n";
        $content .= "Action Requiring Approval:\n{$actionDescription}\n\n";

        // Include relevant state data (excluding sensitive info)
        $stateData = $state->state_data ?? [];
        if (isset($stateData['input'])) {
            $content .= "Input Data:\n";
            $content .= json_encode($stateData['input'], JSON_PRETTY_PRINT) . "\n";
        }

        return $content;
    }

    /**
     * Truncate a string to a maximum length.
     */
    private function truncateString(string $string, int $maxLength): string
    {
        if (strlen($string) <= $maxLength) {
            return $string;
        }

        return substr($string, 0, $maxLength - 3) . '...';
    }
}
