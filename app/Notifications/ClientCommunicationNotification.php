<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\CommunicationType;
use App\Models\Message;
use App\Models\Party;
use App\Models\Project;
use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification for sending approved client communications via email.
 *
 * Supports on-demand delivery to Party contacts who are not application users,
 * with professional formatting based on the communication type.
 */
class ClientCommunicationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Message $message,
        private readonly Party $party,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $_notifiable): array
    {
        $channels = ['database'];

        // Only add mail channel if we have a valid email
        $email = $this->getRecipientEmail();
        if ($email !== null) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $_notifiable): MailMessage
    {
        $communicationType = $this->getCommunicationType();
        $workItem = $this->getWorkItem();
        $workItemTitle = $this->getWorkItemTitle();
        $contactName = $this->party->contact_name ?? $this->party->name;
        $teamName = $this->getTeamName();

        $mailMessage = (new MailMessage)
            ->subject($this->buildSubject($communicationType, $workItemTitle))
            ->greeting("Hello {$contactName},")
            ->line($this->message->content);

        // Add work item context
        if ($workItem !== null) {
            $mailMessage->line('---');
            $mailMessage->line("**Regarding:** {$workItemTitle}");
        }

        // Add action button if we can link to a relevant page
        $actionUrl = $this->getActionUrl($workItem);
        if ($actionUrl !== null) {
            $mailMessage->action('View Details', $actionUrl);
        }

        // Add footer with team branding
        $mailMessage->line('---');
        $mailMessage->line('Best regards,');
        $mailMessage->line("The {$teamName} Team");

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $_notifiable): array
    {
        $workItem = $this->getWorkItem();

        return [
            'type' => 'client_communication',
            'message_id' => $this->message->id,
            'party_id' => $this->party->id,
            'party_name' => $this->party->name,
            'communication_type' => $this->getCommunicationType()?->value,
            'work_item_type' => $this->getWorkItemType(),
            'work_item_id' => $workItem?->id,
            'work_item_title' => $this->getWorkItemTitle(),
            'content_preview' => $this->getContentPreview(),
            'sent_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the recipient email from Party.
     */
    private function getRecipientEmail(): ?string
    {
        return $this->party->contact_email ?? $this->party->email ?? null;
    }

    /**
     * Get the communication type from message metadata.
     */
    private function getCommunicationType(): ?CommunicationType
    {
        $metadata = $this->message->draft_metadata ?? [];
        $typeValue = $metadata['communication_type'] ?? null;

        if ($typeValue === null) {
            return null;
        }

        return CommunicationType::tryFrom($typeValue);
    }

    /**
     * Build the email subject line based on communication type.
     */
    private function buildSubject(
        ?CommunicationType $type,
        string $workItemTitle
    ): string {
        $typeLabel = $type?->label() ?? 'Update';

        return "{$typeLabel}: {$workItemTitle}";
    }

    /**
     * Get the work item (Project or WorkOrder) from message metadata.
     */
    private function getWorkItem(): Project|WorkOrder|null
    {
        $metadata = $this->message->draft_metadata ?? [];
        $entityType = $metadata['entity_type'] ?? null;
        $entityId = $metadata['entity_id'] ?? null;

        if ($entityType === null || $entityId === null) {
            // Try to get from communication thread
            $thread = $this->message->communicationThread;
            if ($thread !== null) {
                return $thread->threadable;
            }

            return null;
        }

        return match ($entityType) {
            'Project' => Project::find($entityId),
            'WorkOrder' => WorkOrder::find($entityId),
            default => null,
        };
    }

    /**
     * Get the work item type as a string.
     */
    private function getWorkItemType(): string
    {
        $workItem = $this->getWorkItem();

        return match (true) {
            $workItem instanceof Project => 'Project',
            $workItem instanceof WorkOrder => 'Work Order',
            default => 'Item',
        };
    }

    /**
     * Get the work item title.
     */
    private function getWorkItemTitle(): string
    {
        $workItem = $this->getWorkItem();

        if ($workItem === null) {
            return 'Your Project';
        }

        // Project uses 'name', WorkOrder uses 'title'
        return $workItem->name ?? $workItem->title ?? 'Untitled';
    }

    /**
     * Get an action URL for the work item (if available for external access).
     *
     * Note: Returns null by default since external clients may not have app access.
     * This can be extended to support a client portal in the future.
     */
    private function getActionUrl(Project|WorkOrder|null $_workItem): ?string
    {
        // For now, we don't expose internal URLs to external clients
        // This can be updated when a client portal is implemented
        return null;
    }

    /**
     * Get the team name for branding.
     */
    private function getTeamName(): string
    {
        $team = $this->party->team;

        if ($team !== null) {
            // Check for workspace settings with custom name
            $settings = $team->workspaceSettings;
            if ($settings !== null && $settings->name !== null) {
                return $settings->name;
            }

            return $team->name;
        }

        return 'Our';
    }

    /**
     * Get a preview of the message content.
     */
    private function getContentPreview(): string
    {
        $content = $this->message->content;

        if (strlen($content) > 200) {
            return substr($content, 0, 197).'...';
        }

        return $content;
    }
}
