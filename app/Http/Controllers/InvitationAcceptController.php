<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Jurager\Teams\Models\Invitation;

class InvitationAcceptController extends Controller
{
    /**
     * Show the invitation accept page.
     */
    public function show(Request $request, Invitation $invitation)
    {
        $invitation->load(['team', 'role']);

        return Inertia::render('invitations/accept', [
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'teamName' => $invitation->team->name,
                'roleName' => $invitation->role?->name ?? 'Member',
            ],
            'isLoggedIn' => auth()->check(),
            'currentUserEmail' => auth()->user()?->email,
        ]);
    }

    /**
     * Accept the invitation.
     */
    public function accept(Request $request, Invitation $invitation)
    {
        $invitation->load(['team', 'role']);

        // If not logged in, redirect to login/register with redirect back
        if (! auth()->check()) {
            // Check if user exists with this email
            $userExists = User::where('email', $invitation->email)->exists();

            $redirectUrl = url()->signedRoute('teams.invitations.accept', $invitation);

            if ($userExists) {
                return redirect()->route('login', [
                    'redirect' => $redirectUrl,
                    'email' => $invitation->email,
                ]);
            }

            return redirect()->route('register', [
                'redirect' => $redirectUrl,
                'email' => $invitation->email,
            ]);
        }

        $user = auth()->user();

        // Verify the logged-in user's email matches the invitation
        if ($user->email !== $invitation->email) {
            return back()->withErrors([
                'email' => 'This invitation was sent to a different email address. Please log in with the correct account.',
            ]);
        }

        // Accept the invitation using the team's method
        $invitation->team->inviteAccept($invitation->id);

        // Switch to the new team
        $user->switchTeam($invitation->team);

        return redirect()->route('settings.index', ['tab' => 'team'])
            ->with('status', "You've been added to {$invitation->team->name}!");
    }
}
