<?php

namespace App\Enums;

enum TimeTrackingMode: string
{
    case Manual = 'manual';
    case Timer = 'timer';
    case AiEstimation = 'ai_estimation';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual Entry',
            self::Timer => 'Timer',
            self::AiEstimation => 'AI Estimation',
        };
    }
}
