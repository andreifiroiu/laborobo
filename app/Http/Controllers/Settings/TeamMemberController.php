<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamMemberController extends Controller
{
    /**
     * Display team members.
     */
    public function index(Team $team)
    {
        $this->authorize('view', $team);

        $members = $team->users()->get()->map(function ($user) use ($team) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_owner' => $user->id === $team->user_id,
                'joined_at' => $user->pivot->created_at->toISOString(),
            ];
        });

        return response()->json([
            'members' => $members,
        ]);
    }

    /**
     * Invite a user to the team.
     */
    public function store(Request $request, Team $team)
    {
        $this->authorize('addTeamMember', $team);

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($team->users()->where('user_id', $user->id)->exists()) {
            return back()->withErrors([
                'email' => 'This user is already a member of the team.'
            ]);
        }

        $team->inviteUser($user);

        return back()->with('status', 'Invitation sent successfully.');
    }

    /**
     * Remove a team member.
     */
    public function destroy(Team $team, User $user)
    {
        $this->authorize('removeTeamMember', $team);

        if ($user->id === $team->user_id) {
            return back()->withErrors([
                'user' => 'Cannot remove the team owner.'
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

        $team->removeUser($user);

        return back()->with('status', 'Member removed successfully.');
    }
}
