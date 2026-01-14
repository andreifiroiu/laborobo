<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'email_project_updates' => 'required|boolean',
            'email_task_assignments' => 'required|boolean',
            'email_approval_requests' => 'required|boolean',
            'email_blockers' => 'required|boolean',
            'email_deadlines' => 'required|boolean',
            'email_weekly_digest' => 'required|boolean',
            'email_agent_activity' => 'required|boolean',
            'push_project_updates' => 'required|boolean',
            'push_task_assignments' => 'required|boolean',
            'push_approval_requests' => 'required|boolean',
            'push_blockers' => 'required|boolean',
            'push_deadlines' => 'required|boolean',
            'push_weekly_digest' => 'required|boolean',
            'push_agent_activity' => 'required|boolean',
            'slack_project_updates' => 'required|boolean',
            'slack_task_assignments' => 'required|boolean',
            'slack_approval_requests' => 'required|boolean',
            'slack_blockers' => 'required|boolean',
            'slack_deadlines' => 'required|boolean',
            'slack_weekly_digest' => 'required|boolean',
            'slack_agent_activity' => 'required|boolean',
        ]);

        $team = $request->user()->currentTeam;
        $user = $request->user();

        $prefs = NotificationPreference::forUser($team, $user);
        $prefs->update($validated);

        return back()->with('success', 'Notification preferences updated.');
    }
}
