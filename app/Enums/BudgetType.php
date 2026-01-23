<?php

declare(strict_types=1);

namespace App\Enums;

enum BudgetType: string
{
    case FixedPrice = 'fixed_price';
    case TimeAndMaterials = 'time_and_materials';
    case MonthlySubscription = 'monthly_subscription';

    public function label(): string
    {
        return match ($this) {
            self::FixedPrice => 'Fixed Price',
            self::TimeAndMaterials => 'Time & Materials',
            self::MonthlySubscription => 'Monthly Subscription',
        };
    }
}
