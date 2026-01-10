<?php

namespace App\Enums;

enum DeliverableType: string
{
    case Document = 'document';
    case Design = 'design';
    case Report = 'report';
    case Code = 'code';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Document => 'Document',
            self::Design => 'Design',
            self::Report => 'Report',
            self::Code => 'Code',
            self::Other => 'Other',
        };
    }
}
