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
                'instructions' => null,
                'tools' => ['task-list', 'work-order-info', 'create-note'],
            ],
            [
                'code' => 'dispatcher',
                'name' => 'Work Dispatcher',
                'type' => 'work-routing',
                'description' => 'Intelligently routes work orders to appropriate team members based on skills and availability',
                'instructions' => null,
                'tools' => ['task-list', 'work-order-info', 'create-note'],
            ],
            [
                'code' => 'content-writer',
                'name' => 'Content Writer',
                'type' => 'content-creation',
                'description' => 'Generates professional documentation, emails, and client communications',
                'instructions' => null,
                'tools' => ['work-order-info', 'create-note'],
            ],
            [
                'code' => 'qa-specialist',
                'name' => 'QA Specialist',
                'type' => 'quality-assurance',
                'description' => 'Reviews work outputs for quality, completeness, and adherence to standards',
                'instructions' => null,
                'tools' => ['task-list', 'work-order-info', 'create-note'],
            ],
            [
                'code' => 'data-analyst',
                'name' => 'Data Analyst',
                'type' => 'data-analysis',
                'description' => 'Analyzes project metrics, generates insights, and creates performance reports',
                'instructions' => null,
                'tools' => ['task-list', 'work-order-info', 'create-note'],
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
