<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AgentChainTemplate;
use Illuminate\Database\Seeder;

class AgentChainTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default template: Dispatcher > PM Copilot > Client Comms
        AgentChainTemplate::firstOrCreate(
            ['name' => 'Dispatcher > PM Copilot > Client Comms'],
            [
                'description' => 'Standard chain for processing new work orders: routes work, generates deliverables, and drafts client communications.',
                'chain_definition' => [
                    'steps' => [
                        [
                            'agent_id' => null, // Will be resolved at runtime to Dispatcher agent
                            'agent_code' => 'dispatcher',
                            'execution_mode' => 'sequential',
                            'conditions' => [],
                            'context_filter_rules' => [
                                'context_include' => ['work_order', 'project', 'client'],
                                'context_exclude' => [],
                            ],
                            'next_step_conditions' => [],
                            'output_transformers' => [],
                        ],
                        [
                            'agent_id' => null, // Will be resolved at runtime to PM Copilot agent
                            'agent_code' => 'pm-copilot',
                            'execution_mode' => 'sequential',
                            'conditions' => [
                                'previous_step_completed' => true,
                            ],
                            'context_filter_rules' => [
                                'context_include' => ['work_order', 'project', 'routing_recommendation'],
                                'context_exclude' => [],
                            ],
                            'next_step_conditions' => [],
                            'output_transformers' => ['flatten'],
                        ],
                        [
                            'agent_id' => null, // Will be resolved at runtime to Client Comms agent
                            'agent_code' => 'client-comms',
                            'execution_mode' => 'sequential',
                            'conditions' => [
                                'previous_step_completed' => true,
                            ],
                            'context_filter_rules' => [
                                'context_include' => ['work_order', 'project', 'client', 'deliverables'],
                                'context_exclude' => ['internal_notes'],
                            ],
                            'next_step_conditions' => [],
                            'output_transformers' => [],
                        ],
                    ],
                ],
                'category' => 'work-order-processing',
                'is_system' => true,
            ]
        );
    }
}
