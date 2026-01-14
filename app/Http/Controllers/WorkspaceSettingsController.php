<?php

namespace App\Http\Controllers;

use App\Models\AIAgent;
use App\Models\AgentActivityLog;
use App\Models\AuditLog;
use App\Models\AvailableIntegration;
use App\Models\BillingInfo;
use App\Models\GlobalAISettings;
use App\Models\Invoice;
use App\Models\NotificationPreference;
use App\Models\TeamIntegration;
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

        // Get notification preferences
        $prefs = NotificationPreference::forUser($team, $user);

        // Get audit log entries
        $auditLogEntries = AuditLog::where('team_id', $team->id)
            ->latest('timestamp')
            ->limit(100)
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'timestamp' => $log->timestamp->toISOString(),
                'actor' => $log->actor,
                'actorName' => $log->actor_name,
                'actorType' => $log->actor_type,
                'action' => $log->action,
                'target' => $log->target,
                'targetId' => $log->target_id,
                'details' => $log->details,
                'ipAddress' => $log->ip_address,
            ]);

        // Get integrations
        $availableIntegrations = AvailableIntegration::where('is_active', true)->get();
        $integrations = $availableIntegrations->map(function ($integration) use ($team) {
            $teamIntegration = TeamIntegration::where('team_id', $team->id)
                ->where('available_integration_id', $integration->id)
                ->first();

            return [
                'id' => $integration->id,
                'code' => $integration->code,
                'name' => $integration->name,
                'category' => $integration->category,
                'description' => $integration->description,
                'icon' => $integration->icon,
                'features' => $integration->features,
                'isActive' => $integration->is_active,
                'connected' => $teamIntegration?->connected ?? false,
                'connectedAt' => $teamIntegration?->connected_at?->toISOString(),
                'lastSyncAt' => $teamIntegration?->last_sync_at?->toISOString(),
                'syncStatus' => $teamIntegration?->sync_status,
                'errorMessage' => $teamIntegration?->error_message,
            ];
        });

        // Get billing information
        $billingInfoModel = BillingInfo::where('team_id', $team->id)->first();
        $billingInfo = $billingInfoModel ? [
            'planName' => $billingInfoModel->plan_name,
            'planPrice' => $billingInfoModel->plan_price,
            'billingCycle' => $billingInfoModel->billing_cycle,
            'billingPeriodStart' => $billingInfoModel->billing_period_start->toISOString(),
            'billingPeriodEnd' => $billingInfoModel->billing_period_end->toISOString(),
            'nextBillingDate' => $billingInfoModel->next_billing_date->toISOString(),
            'usersIncluded' => $billingInfoModel->users_included,
            'usersCurrent' => $billingInfoModel->users_current,
            'projectsIncluded' => $billingInfoModel->projects_included,
            'projectsCurrent' => $billingInfoModel->projects_current,
            'storageGbIncluded' => $billingInfoModel->storage_gb_included,
            'storageGbCurrent' => $billingInfoModel->storage_gb_current,
            'aiRequestsIncluded' => $billingInfoModel->ai_requests_included,
            'aiRequestsCurrent' => $billingInfoModel->ai_requests_current,
            'paymentMethod' => $billingInfoModel->payment_method,
            'cardBrand' => $billingInfoModel->card_brand,
            'cardLast4' => $billingInfoModel->card_last4,
            'cardExpiry' => $billingInfoModel->card_expiry?->toISOString(),
            'status' => $billingInfoModel->status,
            'trialEndsAt' => $billingInfoModel->trial_ends_at?->toISOString(),
        ] : null;

        // Get invoices
        $invoices = Invoice::where('team_id', $team->id)
            ->latest('invoice_date')
            ->limit(12)
            ->get()
            ->map(fn($invoice) => [
                'id' => $invoice->id,
                'invoiceNumber' => $invoice->invoice_number,
                'invoiceDate' => $invoice->invoice_date->toISOString(),
                'dueDate' => $invoice->due_date->toISOString(),
                'amount' => $invoice->amount,
                'status' => $invoice->status,
                'paidAt' => $invoice->paid_at?->toISOString(),
                'description' => $invoice->description,
                'pdfUrl' => $invoice->pdf_url,
            ]);

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
            'notificationPreferences' => [
                'projectUpdates' => [
                    'email' => $prefs->email_project_updates,
                    'push' => $prefs->push_project_updates,
                    'slack' => $prefs->slack_project_updates,
                ],
                'taskAssignments' => [
                    'email' => $prefs->email_task_assignments,
                    'push' => $prefs->push_task_assignments,
                    'slack' => $prefs->slack_task_assignments,
                ],
                'approvalRequests' => [
                    'email' => $prefs->email_approval_requests,
                    'push' => $prefs->push_approval_requests,
                    'slack' => $prefs->slack_approval_requests,
                ],
                'blockers' => [
                    'email' => $prefs->email_blockers,
                    'push' => $prefs->push_blockers,
                    'slack' => $prefs->slack_blockers,
                ],
                'deadlines' => [
                    'email' => $prefs->email_deadlines,
                    'push' => $prefs->push_deadlines,
                    'slack' => $prefs->slack_deadlines,
                ],
                'weeklyDigest' => [
                    'email' => $prefs->email_weekly_digest,
                    'push' => $prefs->push_weekly_digest,
                    'slack' => $prefs->slack_weekly_digest,
                ],
                'agentActivity' => [
                    'email' => $prefs->email_agent_activity,
                    'push' => $prefs->push_agent_activity,
                    'slack' => $prefs->slack_agent_activity,
                ],
            ],
            'auditLogEntries' => $auditLogEntries,
            'integrations' => $integrations,
            'billingInfo' => $billingInfo,
            'invoices' => $invoices,
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
