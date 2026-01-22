<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Jurager\Teams\Models\Invitation;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): Response
    {
        // Check if there's a pending invitation in session
        $invitationId = session('pending_invitation_id');

        if ($invitationId) {
            $invitation = Invitation::with(['team', 'role'])->find($invitationId);

            if ($invitation && $invitation->email === $request->user()->email) {
                // Accept the invitation
                $invitation->team->inviteAccept($invitation->id);

                // Switch user to the invited team
                $request->user()->switchTeam($invitation->team);

                // Clear the session
                session()->forget('pending_invitation_id');

                // Redirect to team settings with success message
                return redirect()->route('settings.index', ['tab' => 'team'])
                    ->with('status', "You've been added to {$invitation->team->name}!");
            }

            // Clear invalid invitation from session
            session()->forget('pending_invitation_id');
        }

        // Default Fortify behavior
        return $request->wantsJson()
            ? new JsonResponse('', 201)
            : redirect()->intended(Fortify::redirects('register'));
    }
}
