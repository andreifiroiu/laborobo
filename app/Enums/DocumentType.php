<?php

namespace App\Enums;

enum DocumentType: string
{
    case Reference = 'reference';
    case Artifact = 'artifact';
    case Evidence = 'evidence';
    case Template = 'template';

    public function label(): string
    {
        return match ($this) {
            self::Reference => 'Reference',
            self::Artifact => 'Artifact',
            self::Evidence => 'Evidence',
            self::Template => 'Template',
        };
    }
}
