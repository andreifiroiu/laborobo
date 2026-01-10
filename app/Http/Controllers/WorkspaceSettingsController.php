<?php

namespace App\Http\Controllers;

use App\Models\AIAgent;
use App\Models\AgentActivityLog;
use App\Models\GlobalAISettings;
use App\Models\WorkspaceSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkspaceSettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam;

        // Get or create workspace settings
        $workspaceSettings = WorkspaceSettings::forTeam($team);

        // Get team members with roles
        $teamMembers = $team->allUsers()->map(function ($member) use ($team) {
            return [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $team->userRole($member)->name ?? 'Owner',
                'avatar' => $member->avatar ?? null,
                'joinedAt' => $member->pivot->created_at ?? $member->created_at,
                'lastActiveAt' => $member->updated_at,
            ];
        });

        // Get AI agents with configurations
        $aiAgents = AIAgent::with(['configurations' => function ($q) use ($team) {
            $q->where('team_id', $team->id);
        }])->get()->map(function ($agent) use ($team) {
            $config = $agent->configurations->first();
            return [
                'id' => $agent->id,
                'code' => $agent->code,
                'name' => $agent->name,
                'type' => $agent->type,
                'description' => $agent->description,
                'capabilities' => $agent->capabilities,
                'status' => $config?->enabled ? 'enabled' : 'disabled',
                'configuration' => $config ? [
                    'enabled' => $config->enabled,
                    'dailyRunLimit' => $config->daily_run_limit,
                    'weeklyRunLimit' => $config->weekly_run_limit,
                    'monthlyBudgetCap' => $config->monthly_budget_cap,
                    'currentMonthSpend' => $config->current_month_spend,
                    'permissions' => [
                        'canCreateWorkOrders' => $config->can_create_work_orders,
                        'canModifyTasks' => $config->can_modify_tasks,
                        'canAccessClientData' => $config->can_access_client_data,
                        'canSendEmails' => $config->can_send_emails,
                        'requiresApproval' => $config->requires_approval,
                    ],
                    'behaviorSettings' => [
                        'verbosityLevel' => $config->verbosity_level,
                        'creativityLevel' => $config->creativity_level,
                        'riskTolerance' => $config->risk_tolerance,
                    ],
                ] : null,
            ];
        });

        // Get global AI settings
        $globalAISettings = GlobalAISettings::forTeam($team);

        // Get recent agent activity
        $agentActivityLogs = AgentActivityLog::where('team_id', $team->id)
            ->with(['agent', 'approver'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'agentId' => $log->ai_agent_id,
                    'agentName' => $log->agent->name,
                    'runType' => $log->run_type,
                    'timestamp' => $log->created_at->toISOString(),
                    'input' => $log->input,
                    'output' => $log->output,
                    'tokensUsed' => $log->tokens_used,
                    'cost' => $log->cost,
                    'approvalStatus' => $log->approval_status,
                    'approvedBy' => $log->approved_by,
                    'approvedAt' => $log->approved_at?->toISOString(),
                    'error' => $log->error,
                ];
            });

        return Inertia::render('settings/index', [
            'workspaceSettings' => [
                'name' => $workspaceSettings->name,
                'timezone' => $workspaceSettings->timezone,
                'workWeekStart' => $workspaceSettings->work_week_start,
                'defaultProjectStatus' => $workspaceSettings->default_project_status,
                'brandColor' => $workspaceSettings->brand_color,
                'logo' => $workspaceSettings->logo,
                'workingHoursStart' => $workspaceSettings->working_hours_start,
                'workingHoursEnd' => $workspaceSettings->working_hours_end,
                'dateFormat' => $workspaceSettings->date_format,
                'currency' => $workspaceSettings->currency,
            ],
            'teamMembers' => $teamMembers,
            'aiAgents' => $aiAgents,
            'globalAISettings' => [
                'totalMonthlyBudget' => $globalAISettings->total_monthly_budget,
                'currentMonthSpend' => $globalAISettings->current_month_spend,
                'perProjectBudgetCap' => $globalAISettings->per_project_budget_cap,
                'approvalRequirements' => [
                    'clientFacingContent' => $globalAISettings->approval_client_facing_content,
                    'financialData' => $globalAISettings->approval_financial_data,
                    'contractualChanges' => $globalAISettings->approval_contractual_changes,
                    'workOrderCreation' => $globalAISettings->approval_work_order_creation,
                    'taskAssignment' => $globalAISettings->approval_task_assignment,
                ],
            ],
            'agentActivityLogs' => $agentActivityLogs,
        ]);
    }

    public function updateWorkspace(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'timezone' => 'required|string',
            'work_week_start' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'brand_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'working_hours_start' => 'required|date_format:H:i',
            'working_hours_end' => 'required|date_format:H:i',
            'date_format' => 'required|string',
            'currency' => 'required|string|size:3',
        ]);

        $team = $request->user()->currentTeam;
        $settings = WorkspaceSettings::forTeam($team);
        $settings->update($validated);

        return back()->with('success', 'Workspace settings updated successfully.');
    }

    public function updateGlobalAI(Request $request)
    {
        $validated = $request->validate([
            'total_monthly_budget' => 'required|numeric|min:0',
            'per_project_budget_cap' => 'required|numeric|min:0',
            'approval_client_facing_content' => 'boolean',
            'approval_financial_data' => 'boolean',
            'approval_contractual_changes' => 'boolean',
            'approval_work_order_creation' => 'boolean',
            'approval_task_assignment' => 'boolean',
        ]);

        $team = $request->user()->currentTeam;
        $settings = GlobalAISettings::forTeam($team);
        $settings->update($validated);

        return back()->with('success', 'Global AI settings updated successfully.');
    }
}
