<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserHasTeam
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Create default team if user has none
        if ($user->allTeams()->count() === 0) {
            $team = $user->createTeam([
                'name' => $user->name . "'s Team",
            ]);

            $user->update(['current_team_id' => $team->id]);
        }

        // Set current team if not set
        if (!$user->current_team_id) {
            $firstTeam = $user->allTeams()->first();
            if ($firstTeam) {
                $user->update(['current_team_id' => $firstTeam->id]);
            }
        }

        return $next($request);
    }
}
