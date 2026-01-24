<?php

declare(strict_types=1);

namespace App\Enums;

enum TriggerEntityType: string
{
    case WorkOrder = 'work_order';
    case Task = 'task';
    case Deliverable = 'deliverable';

    /**
     * Get the human-readable label for the entity type.
     */
    public function label(): string
    {
        return match ($this) {
            self::WorkOrder => 'Work Order',
            self::Task => 'Task',
            self::Deliverable => 'Deliverable',
        };
    }

    /**
     * Get the corresponding model class for this entity type.
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::WorkOrder => \App\Models\WorkOrder::class,
            self::Task => \App\Models\Task::class,
            self::Deliverable => \App\Models\Deliverable::class,
        };
    }
}
