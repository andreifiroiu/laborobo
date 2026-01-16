<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\DeliverableStatus;
use App\Models\Deliverable;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DeliverableStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Deliverable $deliverable,
        private readonly DeliverableStatus $oldStatus,
        private readonly DeliverableStatus $newStatus,
        private readonly User $changedBy
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $workOrder = $this->deliverable->workOrder;

        return [
            'deliverable_id' => $this->deliverable->id,
            'deliverable_title' => $this->deliverable->title,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'changed_by_user_id' => $this->changedBy->id,
            'changed_by_user_name' => $this->changedBy->name,
            'work_order_id' => $workOrder?->id,
            'work_order_title' => $workOrder?->title,
            'link' => route('deliverables.show', $this->deliverable),
        ];
    }
}
