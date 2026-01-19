<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CommunicationThread;
use App\Models\Message;
use App\Models\NotificationPreference;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Message $message,
        private readonly CommunicationThread $thread,
        private readonly User $author,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $this->shouldSendEmail($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Check if email should be sent based on user preferences.
     */
    private function shouldSendEmail(User $user): bool
    {
        $workItem = $this->thread->threadable;
        $team = $workItem?->team ?? null;

        if ($team === null) {
            return false;
        }

        $preferences = NotificationPreference::forUser($team, $user);

        // Use project_updates preference for mention notifications
        return $preferences->email_project_updates ?? true;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $workItemType = $this->getWorkItemType();
        $workItemTitle = $this->getWorkItemTitle();
        $contentPreview = $this->getContentPreview();

        return (new MailMessage)
            ->subject("You were mentioned in {$workItemTitle}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->author->name} mentioned you in a message on {$workItemType} \"{$workItemTitle}\".")
            ->line('**Message:**')
            ->line($contentPreview)
            ->action('View Message', $this->getWorkItemUrl())
            ->line('Click the button above to view the full conversation.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        unset($notifiable); // Required by interface but unused

        return [
            'type' => 'mention',
            'message_id' => $this->message->id,
            'thread_id' => $this->thread->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'content_preview' => $this->getContentPreview(),
            'work_item_type' => $this->getWorkItemType(),
            'work_item_id' => $this->thread->threadable?->id,
            'work_item_title' => $this->getWorkItemTitle(),
            'link' => $this->getWorkItemUrl(),
        ];
    }

    /**
     * Get a preview of the message content.
     */
    private function getContentPreview(): string
    {
        $content = $this->message->content;

        if (strlen($content) > 150) {
            return substr($content, 0, 147).'...';
        }

        return $content;
    }

    /**
     * Get the work item type label.
     */
    private function getWorkItemType(): string
    {
        $workItem = $this->thread->threadable;

        return match (true) {
            $workItem instanceof Task => 'Task',
            $workItem instanceof WorkOrder => 'Work Order',
            $workItem instanceof Project => 'Project',
            default => 'Item',
        };
    }

    /**
     * Get the work item title.
     */
    private function getWorkItemTitle(): string
    {
        $workItem = $this->thread->threadable;

        if ($workItem === null) {
            return 'Unknown';
        }

        return $workItem->title ?? $workItem->name ?? 'Untitled';
    }

    /**
     * Get the URL to the work item.
     */
    private function getWorkItemUrl(): string
    {
        $workItem = $this->thread->threadable;

        return match (true) {
            $workItem instanceof Task => route('tasks.show', $workItem),
            $workItem instanceof WorkOrder => route('work-orders.show', $workItem),
            $workItem instanceof Project => route('projects.show', $workItem),
            default => route('today'),
        };
    }
}
