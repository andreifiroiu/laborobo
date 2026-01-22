<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamMemberController extends Controller
{
    /**
     * Invite a user to the team (or send invitation if user doesn't exist).
     */
    public function store(Request $request)
    {
        $team = $request->user()->currentTeam;

        $this->authorize('addTeamMember', $team);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        // Check if email already belongs to the team
        if ($team->hasUserWithEmail($validated['email'])) {
            return back()->withErrors([
                'email' => 'This user is already a member of the team.',
            ]);
        }

        // Check if there's already a pending invitation
        if ($team->invitations()->where('email', $validated['email'])->exists()) {
            return back()->withErrors([
                'email' => 'An invitation has already been sent to this email address.',
            ]);
        }

        // Get the role by ID to get its code
        $role = $team->roles()->find($validated['role_id']);

        if (! $role) {
            return back()->withErrors([
                'role_id' => 'The selected role is not valid for this team.',
            ]);
        }

        // Create invitation (works for both existing and new users)
        $team->inviteUser($validated['email'], $role->code);

        return back()->with('status', 'Invitation sent successfully.');
    }

    /**
     * Update a team member's role.
     */
    public function update(Request $request, User $user)
    {
        $team = $request->user()->currentTeam;

        $this->authorize('updateTeamMember', $team);

        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        // Cannot change owner role
        if ($user->id === $team->user_id) {
            return back()->withErrors([
                'user' => 'Cannot change the team owner\'s role.',
            ]);
        }

        // Get the role by ID to get its code
        $role = $team->roles()->find($validated['role_id']);

        if (! $role) {
            return back()->withErrors([
                'role_id' => 'The selected role is not valid for this team.',
            ]);
        }

        $team->updateUser($user, $role->code);

        return back()->with('status', 'Member role updated successfully.');
    }

    /**
     * Remove a team member.
     */
    public function destroy(Request $request, User $user)
    {
        $team = $request->user()->currentTeam;

        $this->authorize('removeTeamMember', $team);

        if ($user->id === $team->user_id) {
            return back()->withErrors([
                'user' => 'Cannot remove the team owner.',
            ]);
        }

        if ($user->id === Auth::id() && $team->id === Auth::user()->current_team_id) {
            $newTeam = Auth::user()->allTeams()
                ->where('id', '!=', $team->id)
                ->first();

            if ($newTeam) {
                Auth::user()->switchTeam($newTeam);
            }
        }

        $team->deleteUser($user);

        return back()->with('status', 'Member removed successfully.');
    }
}
