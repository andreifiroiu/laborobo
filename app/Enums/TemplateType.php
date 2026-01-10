<?php

namespace App\Enums;

enum TemplateType: string
{
    case Project = 'project';
    case WorkOrder = 'work-order';
    case Document = 'document';
}
