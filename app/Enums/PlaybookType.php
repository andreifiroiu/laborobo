<?php

namespace App\Enums;

enum PlaybookType: string
{
    case SOP = 'sop';
    case Checklist = 'checklist';
    case Template = 'template';
    case AcceptanceCriteria = 'acceptance_criteria';
}
