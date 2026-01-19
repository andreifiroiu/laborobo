<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\NotificationPreference;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RejectionFeedbackNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Task|WorkOrder $item,
        private readonly User $reviewer,
        private readonly string $feedback,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Check if user has email_blockers preference enabled
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
        $team = $this->item->team;
        if ($team === null) {
            return false;
        }

        $preferences = NotificationPreference::forUser($team, $user);

        return $preferences->email_blockers ?? true;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $itemType = $this->item instanceof Task ? 'Task' : 'Work Order';
        $itemTitle = $this->item->title;

        return (new MailMessage)
            ->subject("Revision Requested: {$itemTitle}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your {$itemType} \"{$itemTitle}\" requires revisions.")
            ->line("**Reviewer:** {$this->reviewer->name}")
            ->line('**Feedback:**')
            ->line($this->feedback)
            ->action('View Details', $this->getItemUrl())
            ->line('Please address the feedback and resubmit for review.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $isTask = $this->item instanceof Task;
        $workOrder = $isTask ? $this->item->workOrder : $this->item;
        $project = $this->item->project;

        return [
            'type' => 'rejection_feedback',
            'item_type' => $isTask ? 'task' : 'work_order',
            'item_id' => $this->item->id,
            'item_title' => $this->item->title,
            'reviewer_id' => $this->reviewer->id,
            'reviewer_name' => $this->reviewer->name,
            'feedback' => $this->feedback,
            'work_order_id' => $workOrder?->id,
            'work_order_title' => $workOrder?->title,
            'project_id' => $project?->id,
            'project_name' => $project?->name,
            'link' => $this->getItemUrl(),
        ];
    }

    /**
     * Get the URL to the item.
     */
    private function getItemUrl(): string
    {
        if ($this->item instanceof Task) {
            return route('tasks.show', $this->item);
        }

        return route('work-orders.show', $this->item);
    }
}
