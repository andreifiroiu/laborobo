<?php

namespace App\Http\Controllers\Directory;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamMemberController extends Controller
{
    /**
     * Update the skills for a team member.
     */
    public function updateSkills(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();
        $team = $currentUser->currentTeam;

        // Verify the user belongs to the team
        if (!$user->belongsToTeam($team)) {
            abort(403, 'User does not belong to your team.');
        }

        $validated = $request->validate([
            'skills' => 'required|array',
            'skills.*.name' => 'required|string|max:255',
            'skills.*.proficiency' => 'required|integer|min:1|max:3',
        ]);

        DB::transaction(function () use ($user, $validated) {
            // Delete all existing skills for this user
            $user->skills()->delete();

            // Create new skills
            foreach ($validated['skills'] as $skillData) {
                UserSkill::create([
                    'user_id' => $user->id,
                    'skill_name' => $skillData['name'],
                    'proficiency' => $skillData['proficiency'],
                ]);
            }
        });

        return back()->with('success', 'Skills updated successfully.');
    }

    /**
     * Update the capacity for a team member.
     */
    public function updateCapacity(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();
        $team = $currentUser->currentTeam;

        // Verify the user belongs to the team
        if (!$user->belongsToTeam($team)) {
            abort(403, 'User does not belong to your team.');
        }

        $validated = $request->validate([
            'capacityHoursPerWeek' => 'required|integer|min:0|max:168',
            'currentWorkloadHours' => 'required|integer|min:0|max:168',
            'role' => 'nullable|string|max:255',
        ]);

        $user->update([
            'capacity_hours_per_week' => $validated['capacityHoursPerWeek'],
            'current_workload_hours' => $validated['currentWorkloadHours'],
            'role' => $validated['role'] ?? $user->role,
        ]);

        return back()->with('success', 'Capacity updated successfully.');
    }
}
