<?php

declare(strict_types=1);

namespace App\Enums;

enum AgentType: string
{
    case ProjectManagement = 'project-management';
    case WorkRouting = 'work-routing';
    case ContentCreation = 'content-creation';
    case QualityAssurance = 'quality-assurance';
    case DataAnalysis = 'data-analysis';
    case ClientCommunication = 'client-communication';

    /**
     * Get the human-readable label for the agent type.
     */
    public function label(): string
    {
        return match ($this) {
            self::ProjectManagement => 'Project Management',
            self::WorkRouting => 'Work Routing',
            self::ContentCreation => 'Content Creation',
            self::QualityAssurance => 'Quality Assurance',
            self::DataAnalysis => 'Data Analysis',
            self::ClientCommunication => 'Client Communication',
        };
    }
}
