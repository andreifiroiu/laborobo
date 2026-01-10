<?php

namespace App\Enums;

enum AgentType: string
{
    case ProjectManagement = 'project-management';
    case WorkRouting = 'work-routing';
    case ContentCreation = 'content-creation';
    case QualityAssurance = 'quality-assurance';
    case DataAnalysis = 'data-analysis';
}
