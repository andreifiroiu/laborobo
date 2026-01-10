<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AIAgent;
use App\Models\AgentActivityLog;
use App\Models\AgentConfiguration;
use Illuminate\Http\Request;

class AIAgentsController extends Controller
{
    public function toggleAgent(Request $request, AIAgent $agent)
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $team = $request->user()->currentTeam;

        $config = AgentConfiguration::firstOrCreate(
            ['team_id' => $team->id, 'ai_agent_id' => $agent->id]
        );

        $config->update(['enabled' => $validated['enabled']]);

        return back()->with('success', $validated['enabled'] ? 'Agent enabled' : 'Agent disabled');
    }

    public function updateConfig(Request $request, AIAgent $agent)
    {
        $validated = $request->validate([
            'daily_run_limit' => 'required|integer|min:1',
            'weekly_run_limit' => 'required|integer|min:1',
            'monthly_budget_cap' => 'required|numeric|min:0',
            'can_create_work_orders' => 'boolean',
            'can_modify_tasks' => 'boolean',
            'can_access_client_data' => 'boolean',
            'can_send_emails' => 'boolean',
            'requires_approval' => 'boolean',
            'verbosity_level' => 'required|in:concise,balanced,detailed',
            'creativity_level' => 'required|in:low,balanced,high',
            'risk_tolerance' => 'required|in:low,medium,high',
        ]);

        $team = $request->user()->currentTeam;

        $config = AgentConfiguration::where('team_id', $team->id)
            ->where('ai_agent_id', $agent->id)
            ->firstOrFail();

        $config->update($validated);

        return back()->with('success', 'Agent configuration updated');
    }

    public function approveOutput(Request $request, AgentActivityLog $log)
    {
        $this->authorize('update', $log->team);

        $log->update([
            'approval_status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Agent output approved');
    }

    public function rejectOutput(Request $request, AgentActivityLog $log)
    {
        $this->authorize('update', $log->team);

        $log->update([
            'approval_status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Agent output rejected');
    }
}
