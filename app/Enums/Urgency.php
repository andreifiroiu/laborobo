<?php

namespace App\Enums;

enum Urgency: string
{
    case Urgent = 'urgent';
    case High = 'high';
    case Normal = 'normal';
}
