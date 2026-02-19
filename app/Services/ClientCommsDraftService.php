<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AIConfidence;
use App\Enums\AuthorType;
use App\Enums\CommunicationType;
use App\Enums\DraftStatus;
use App\Enums\InboxItemType;
use App\Enums\MessageType;
use App\Enums\SourceType;
use App\Enums\Urgency;
use App\Jobs\ClientCommunicationDeliveryJob;
use App\Models\CommunicationThread;
use App\Models\InboxItem;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AI\LLMService;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Service for creating and managing client communication drafts.
 *
 * Handles the complete lifecycle of AI-generated client communication drafts:
 * - Draft creation using the ClientCommsAgent
 * - InboxItem creation for approval workflow
 * - Draft approval with delivery job dispatch
 * - Draft rejection with feedback storage
 * - Draft editing before approval
 */
class ClientCommsDraftService
{
    private const CLIENT_COMMS_AGENT_SOURCE_ID = 'agent-client-comms';

    public function __construct(
        private readonly CommsContextBuilder $contextBuilder,
        private readonly AgentRunner $_agentRunner,
        private readonly ToolGateway $_toolGateway,
        private readonly AgentBudgetService $_budgetService,
        private readonly ?LLMService $llmService = null,
    ) {}

    /**
     * Create a communication draft for the given entity.
     *
     * Builds context, executes the ClientCommsAgent to generate draft content,
     * and stores the result as a Message with draft status.
     *
     * @param  Project|WorkOrder  $entity  The entity to create a draft for
     * @param  CommunicationType  $type  The type of communication to draft
     * @param  string|null  $userNotes  Optional notes from the user for context
     */
    public function createDraft(
        Project|WorkOrder $entity,
        CommunicationType $type,
        ?string $userNotes = null,
    ): Message {
        // Build context for the agent
        $context = $this->contextBuilder->buildFullContext($entity, $type);

        // Get or create the communication thread
        $thread = $this->getOrCreateThread($entity);

        // Determine confidence based on context completeness
        $confidence = $this->determineConfidence($type, $context);

        // Generate draft content using the agent
        $draftContent = $this->generateDraftContent($entity, $type, $context, $userNotes);

        // Create the draft message
        return Message::create([
            'communication_thread_id' => $thread->id,
            'author_id' => null,
            'author_type' => AuthorType::AiAgent,
            'content' => $draftContent,
            'type' => $this->mapCommunicationTypeToMessageType($type),
            'draft_status' => DraftStatus::Draft,
            'draft_metadata' => [
                'communication_type' => $type->value,
                'confidence' => $confidence->value,
                'context_summary' => $this->buildContextSummary($context),
                'user_notes' => $userNotes,
                'target_language' => $context->metadata['target_language'] ?? 'en',
                'entity_type' => class_basename($entity),
                'entity_id' => $entity->id,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create an InboxItem for draft approval.
     *
     * @param  Message  $draft  The draft message requiring approval
     * @param  Project|WorkOrder  $entity  The related entity
     */
    public function createApprovalItem(Message $draft, Project|WorkOrder $entity): InboxItem
    {
        $project = $entity instanceof Project ? $entity : $entity->project;
        $workOrder = $entity instanceof WorkOrder ? $entity : null;

        $communicationType = CommunicationType::tryFrom($draft->draft_metadata['communication_type'] ?? '');
        $confidence = AIConfidence::tryFrom($draft->draft_metadata['confidence'] ?? 'medium')
            ?? AIConfidence::Medium;

        return InboxItem::create([
            'team_id' => $entity->team_id,
            'type' => InboxItemType::AgentDraft,
            'title' => $this->generateApprovalItemTitle($communicationType, $entity),
            'content_preview' => $this->truncateContent($draft->content, 200),
            'full_content' => $draft->content,
            'source_id' => self::CLIENT_COMMS_AGENT_SOURCE_ID,
            'source_type' => SourceType::AIAgent,
            'source_name' => 'Client Comms Agent',
            'related_work_order_id' => $workOrder?->id,
            'related_work_order_title' => $workOrder?->title,
            'related_project_id' => $project?->id,
            'related_project_name' => $project?->name,
            'approvable_type' => Message::class,
            'approvable_id' => $draft->id,
            'urgency' => $this->determineUrgency($communicationType),
            'ai_confidence' => $confidence,
        ]);
    }

    /**
     * Approve a draft and trigger delivery.
     *
     * @param  Message  $draft  The draft to approve
     * @param  User  $approver  The user approving the draft
     */
    public function approveDraft(Message $draft, User $approver): void
    {
        if ($draft->draft_status !== DraftStatus::Draft) {
            throw new InvalidArgumentException('Cannot approve a draft that is not in Draft status');
        }

        // Mark the draft as approved
        $draft->markAsApproved($approver);

        // Mark the inbox item as approved
        $inboxItem = $this->findInboxItemForDraft($draft);
        if ($inboxItem !== null) {
            $inboxItem->markAsApproved();
        }

        // Dispatch the delivery job
        $party = $this->getPartyForDraft($draft);
        if ($party !== null) {
            ClientCommunicationDeliveryJob::dispatch($draft, $party);
        }
    }

    /**
     * Reject a draft with feedback.
     *
     * @param  Message  $draft  The draft to reject
     * @param  string  $reason  The reason for rejection
     */
    public function rejectDraft(Message $draft, string $reason): void
    {
        if ($draft->draft_status !== DraftStatus::Draft) {
            throw new InvalidArgumentException('Cannot reject a draft that is not in Draft status');
        }

        // Mark the draft as rejected
        $draft->markAsRejected($reason);

        // Mark the inbox item as rejected
        $inboxItem = $this->findInboxItemForDraft($draft);
        if ($inboxItem !== null) {
            $inboxItem->markAsRejected();
        }
    }

    /**
     * Update the content of a draft.
     *
     * @param  Message  $draft  The draft to update
     * @param  string  $newContent  The new content for the draft
     * @return Message The updated draft
     */
    public function updateDraft(Message $draft, string $newContent): Message
    {
        if ($draft->draft_status !== DraftStatus::Draft) {
            throw new InvalidArgumentException('Cannot edit a draft that is not in Draft status');
        }

        $draft->update([
            'content' => $newContent,
            'edited_at' => now(),
        ]);

        return $draft->fresh() ?? $draft;
    }

    /**
     * Get or create a communication thread for the entity.
     */
    private function getOrCreateThread(Project|WorkOrder $entity): CommunicationThread
    {
        $thread = $entity->communicationThread;

        if ($thread === null) {
            $thread = CommunicationThread::create([
                'team_id' => $entity->team_id,
                'threadable_type' => get_class($entity),
                'threadable_id' => $entity->id,
                'message_count' => 0,
                'last_activity' => now(),
            ]);
        }

        return $thread;
    }

    /**
     * Generate draft content using the LLM with template fallback.
     */
    private function generateDraftContent(
        Project|WorkOrder $entity,
        CommunicationType $type,
        \App\ValueObjects\AgentContext $context,
        ?string $userNotes,
    ): string {
        $party = $entity instanceof Project
            ? $entity->party
            : $entity->project?->party;

        $contactName = $party?->contact_name ?? 'Valued Client';
        $entityName = $entity instanceof Project ? $entity->name : $entity->title;
        $targetLanguage = $context->metadata['target_language'] ?? 'en';

        // Try LLM-based generation first
        if ($this->llmService !== null) {
            $llmDraft = $this->generateDraftViaLLM($entity, $type, $context, $contactName, $entityName, $targetLanguage, $userNotes);
            if ($llmDraft !== null) {
                return $llmDraft;
            }
        }

        // Fall back to template-based content
        return match ($type) {
            CommunicationType::StatusUpdate => $this->generateStatusUpdateDraft($contactName, $entityName, $context),
            CommunicationType::DeliverableNotification => $this->generateDeliverableNotificationDraft($contactName, $entityName, $context),
            CommunicationType::ClarificationRequest => $this->generateClarificationRequestDraft($contactName, $entityName, $userNotes),
            CommunicationType::MilestoneAnnouncement => $this->generateMilestoneAnnouncementDraft($contactName, $entityName, $context),
        };
    }

    /**
     * Attempt to generate draft content via LLM.
     */
    private function generateDraftViaLLM(
        Project|WorkOrder $entity,
        CommunicationType $type,
        \App\ValueObjects\AgentContext $context,
        string $contactName,
        string $entityName,
        string $targetLanguage,
        ?string $userNotes,
    ): ?string {
        try {
            $clientCommsAgent = new \App\Agents\ClientCommsAgent(
                new \App\Models\AIAgent,
                new \App\Models\AgentConfiguration,
                $this->_toolGateway,
                $this->_budgetService,
            );

            $languageInstructions = $clientCommsAgent->buildLanguageInstructions($targetLanguage);
            $systemPrompt = $clientCommsAgent->getBaseInstructions()."\n\n".$languageInstructions;

            $contextSummary = '';
            if (! $context->isEmpty()) {
                $contextSummary = "\n\nContext:\n".$context->toPromptString();
            }

            $userPrompt = "Draft a {$type->label()} communication for {$entityName}.\n"
                ."Recipient: {$contactName}\n"
                .($userNotes !== null ? "Notes: {$userNotes}\n" : '')
                .$contextSummary
                ."\n\nRespond with ONLY the communication text (greeting, body, closing). Do not include JSON wrapping.";

            $response = $this->llmService->complete(
                systemPrompt: $systemPrompt,
                userPrompt: $userPrompt,
                teamId: $entity->team_id,
            );

            if ($response !== null && $response->content !== '') {
                return $response->content;
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('LLM draft generation failed, falling back to templates', [
                'entity_type' => class_basename($entity),
                'entity_id' => $entity->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function generateStatusUpdateDraft(string $contactName, string $entityName, \App\ValueObjects\AgentContext $context): string
    {
        $progress = $context->projectContext['work_item']['progress'] ?? 0;
        $status = $context->projectContext['work_item']['status'] ?? 'in progress';

        return <<<DRAFT
Dear {$contactName},

I wanted to provide you with a brief update on {$entityName}.

**Current Status:** {$status}
**Progress:** {$progress}%

We continue to make good progress and remain on track. Our team is focused on delivering quality results that meet your expectations.

Please let me know if you have any questions or would like to discuss any aspects of the project in more detail.

Best regards
DRAFT;
    }

    private function generateDeliverableNotificationDraft(string $contactName, string $entityName, \App\ValueObjects\AgentContext $context): string
    {
        $deliverables = $context->projectContext['work_item']['deliverables'] ?? [];
        $deliverableInfo = ! empty($deliverables)
            ? "We have completed the following deliverable(s) for your review:\n- ".collect($deliverables)->pluck('title')->implode("\n- ")
            : 'We have completed a deliverable for your review.';

        return <<<DRAFT
Dear {$contactName},

I'm pleased to inform you that we have a deliverable ready for your review on {$entityName}.

{$deliverableInfo}

Please take a moment to review the delivered work and let us know if it meets your expectations or if any adjustments are needed.

We look forward to your feedback.

Best regards
DRAFT;
    }

    private function generateClarificationRequestDraft(string $contactName, string $entityName, ?string $userNotes): string
    {
        $clarificationContext = $userNotes ?? 'some aspects of the project requirements';

        return <<<DRAFT
Dear {$contactName},

I hope this message finds you well. I'm reaching out regarding {$entityName} to request some clarification.

To ensure we deliver exactly what you need, we would appreciate your input on {$clarificationContext}.

Could you please provide some additional details at your earliest convenience? This will help us proceed efficiently and ensure the final result aligns with your expectations.

Thank you for your time, and please don't hesitate to reach out if you have any questions.

Best regards
DRAFT;
    }

    private function generateMilestoneAnnouncementDraft(string $contactName, string $entityName, \App\ValueObjects\AgentContext $context): string
    {
        $progress = $context->projectContext['work_item']['progress'] ?? 50;

        return <<<DRAFT
Dear {$contactName},

I'm excited to share some great news about {$entityName}.

We have reached a significant milestone in our work together! This achievement represents an important step forward in delivering value for your organization.

**Current Progress:** {$progress}%

Thank you for your continued partnership and support. We're committed to maintaining this momentum as we work towards the successful completion of this project.

If you'd like to discuss this milestone or have any questions, please don't hesitate to reach out.

Best regards
DRAFT;
    }

    /**
     * Determine AI confidence based on communication type and context completeness.
     */
    private function determineConfidence(CommunicationType $type, \App\ValueObjects\AgentContext $context): AIConfidence
    {
        $hasProjectContext = ! empty($context->projectContext);
        $hasClientContext = ! empty($context->clientContext);
        $hasWorkItemDetails = isset($context->projectContext['work_item']);
        $contextCompleteness = ($hasProjectContext ? 0.4 : 0) + ($hasClientContext ? 0.3 : 0) + ($hasWorkItemDetails ? 0.3 : 0);

        return match ($type) {
            CommunicationType::StatusUpdate => $contextCompleteness >= 0.7
                ? AIConfidence::High
                : ($contextCompleteness >= 0.4 ? AIConfidence::Medium : AIConfidence::Low),
            CommunicationType::DeliverableNotification => $hasWorkItemDetails && isset($context->projectContext['work_item']['deliverables'])
                ? AIConfidence::High
                : ($hasWorkItemDetails ? AIConfidence::Medium : AIConfidence::Low),
            CommunicationType::ClarificationRequest => $hasClientContext
                ? AIConfidence::Medium
                : AIConfidence::Low,
            CommunicationType::MilestoneAnnouncement => $hasWorkItemDetails
                ? AIConfidence::High
                : AIConfidence::Medium,
        };
    }

    /**
     * Build a summary of the context used for the draft.
     *
     * @return array<string, mixed>
     */
    private function buildContextSummary(\App\ValueObjects\AgentContext $context): array
    {
        return [
            'has_project_context' => ! empty($context->projectContext),
            'has_client_context' => ! empty($context->clientContext),
            'has_org_context' => ! empty($context->orgContext),
            'token_estimate' => $context->getTokenEstimate(),
            'work_item_type' => $context->metadata['entity_type'] ?? null,
        ];
    }

    /**
     * Map CommunicationType to MessageType.
     */
    private function mapCommunicationTypeToMessageType(CommunicationType $type): MessageType
    {
        return match ($type) {
            CommunicationType::StatusUpdate => MessageType::StatusUpdate,
            CommunicationType::DeliverableNotification, CommunicationType::MilestoneAnnouncement => MessageType::Message,
            CommunicationType::ClarificationRequest => MessageType::Question,
        };
    }

    /**
     * Generate a title for the approval inbox item.
     */
    private function generateApprovalItemTitle(?CommunicationType $type, Project|WorkOrder $entity): string
    {
        $typeLabel = $type?->label() ?? 'Communication';
        $entityName = $entity instanceof Project ? $entity->name : $entity->title;

        return "Review: {$typeLabel} for {$entityName}";
    }

    /**
     * Determine urgency based on communication type.
     */
    private function determineUrgency(?CommunicationType $type): Urgency
    {
        return match ($type) {
            CommunicationType::ClarificationRequest => Urgency::High,
            CommunicationType::DeliverableNotification => Urgency::Normal,
            CommunicationType::StatusUpdate => Urgency::Normal,
            CommunicationType::MilestoneAnnouncement => Urgency::Normal,
            default => Urgency::Normal,
        };
    }

    /**
     * Truncate content for preview.
     */
    private function truncateContent(string $content, int $maxLength): string
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        return substr($content, 0, $maxLength - 3).'...';
    }

    /**
     * Find the InboxItem associated with a draft.
     */
    private function findInboxItemForDraft(Message $draft): ?InboxItem
    {
        return InboxItem::query()
            ->where('approvable_type', Message::class)
            ->where('approvable_id', $draft->id)
            ->where('type', InboxItemType::AgentDraft)
            ->first();
    }

    /**
     * Get the Party associated with a draft's thread.
     */
    private function getPartyForDraft(Message $draft): ?\App\Models\Party
    {
        $thread = $draft->communicationThread;

        if ($thread === null) {
            return null;
        }

        $threadable = $thread->threadable;

        if ($threadable instanceof Project) {
            return $threadable->party;
        }

        if ($threadable instanceof WorkOrder) {
            return $threadable->project?->party;
        }

        return null;
    }
}
