<?php

namespace Database\Seeders;

use App\Models\AIAgent;
use Illuminate\Database\Seeder;

class AIAgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $agents = [
            [
                'code' => 'pm-copilot',
                'name' => 'PM Copilot',
                'type' => 'project-management',
                'description' => 'Assists with project planning, status updates, and timeline management',
                'capabilities' => ['draft_status_reports', 'suggest_timelines', 'identify_risks', 'generate_summaries'],
            ],
            [
                'code' => 'dispatcher',
                'name' => 'Work Dispatcher',
                'type' => 'work-routing',
                'description' => 'Intelligently routes work orders to appropriate team members based on skills and availability',
                'capabilities' => ['analyze_workload', 'match_skills', 'optimize_assignments', 'balance_capacity'],
            ],
            [
                'code' => 'content-writer',
                'name' => 'Content Writer',
                'type' => 'content-creation',
                'description' => 'Generates professional documentation, emails, and client communications',
                'capabilities' => ['draft_emails', 'write_proposals', 'create_documentation', 'summarize_meetings'],
            ],
            [
                'code' => 'qa-specialist',
                'name' => 'QA Specialist',
                'type' => 'quality-assurance',
                'description' => 'Reviews work outputs for quality, completeness, and adherence to standards',
                'capabilities' => ['review_deliverables', 'check_completeness', 'suggest_improvements', 'validate_requirements'],
            ],
            [
                'code' => 'data-analyst',
                'name' => 'Data Analyst',
                'type' => 'data-analysis',
                'description' => 'Analyzes project metrics, generates insights, and creates performance reports',
                'capabilities' => ['analyze_metrics', 'identify_trends', 'generate_reports', 'forecast_outcomes'],
            ],
        ];

        foreach ($agents as $agentData) {
            AIAgent::firstOrCreate(
                ['code' => $agentData['code']],
                $agentData
            );
        }
    }
}
