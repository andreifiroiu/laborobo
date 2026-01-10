<?php

namespace App\Http\Controllers\Directory;

use App\Enums\CommunicationPreference;
use App\Enums\ContactEngagementType;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Party;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    /**
     * Store a newly created contact in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'partyId' => 'required|exists:parties,id',
            'title' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'engagementType' => ['required', 'string', Rule::in(['requester', 'approver', 'stakeholder', 'billing'])],
            'communicationPreference' => ['required', 'string', Rule::in(['email', 'phone', 'slack'])],
            'timezone' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'status' => 'nullable|string|in:active,inactive',
            'setPrimaryContact' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $team = $user->currentTeam;

        // Verify party belongs to team
        $party = Party::where('id', $validated['partyId'])
            ->where('team_id', $team->id)
            ->firstOrFail();

        $contact = Contact::create([
            'team_id' => $team->id,
            'party_id' => $validated['partyId'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'title' => $validated['title'] ?? null,
            'role' => $validated['role'] ?? null,
            'engagement_type' => ContactEngagementType::from($validated['engagementType']),
            'communication_preference' => CommunicationPreference::from($validated['communicationPreference']),
            'timezone' => $validated['timezone'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'status' => $validated['status'] ?? 'active',
        ]);

        // Set as primary contact if requested and party doesn't have one
        if (($validated['setPrimaryContact'] ?? false) || !$party->primary_contact_id) {
            $party->update([
                'primary_contact_id' => $contact->id,
                'last_activity' => now(),
            ]);
        }

        return back()->with('success', 'Contact created successfully.');
    }

    /**
     * Update the specified contact in storage.
     */
    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'partyId' => 'sometimes|required|exists:parties,id',
            'title' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'engagementType' => ['sometimes', 'required', 'string', Rule::in(['requester', 'approver', 'stakeholder', 'billing'])],
            'communicationPreference' => ['sometimes', 'required', 'string', Rule::in(['email', 'phone', 'slack'])],
            'timezone' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'status' => 'nullable|string|in:active,inactive',
            'setPrimaryContact' => 'nullable|boolean',
        ]);

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (isset($validated['email'])) {
            $updateData['email'] = $validated['email'];
        }
        if (array_key_exists('phone', $validated)) {
            $updateData['phone'] = $validated['phone'];
        }
        if (isset($validated['partyId'])) {
            // Verify party belongs to team
            Party::where('id', $validated['partyId'])
                ->where('team_id', $contact->team_id)
                ->firstOrFail();
            $updateData['party_id'] = $validated['partyId'];
        }
        if (array_key_exists('title', $validated)) {
            $updateData['title'] = $validated['title'];
        }
        if (array_key_exists('role', $validated)) {
            $updateData['role'] = $validated['role'];
        }
        if (isset($validated['engagementType'])) {
            $updateData['engagement_type'] = ContactEngagementType::from($validated['engagementType']);
        }
        if (isset($validated['communicationPreference'])) {
            $updateData['communication_preference'] = CommunicationPreference::from($validated['communicationPreference']);
        }
        if (array_key_exists('timezone', $validated)) {
            $updateData['timezone'] = $validated['timezone'];
        }
        if (array_key_exists('notes', $validated)) {
            $updateData['notes'] = $validated['notes'];
        }
        if (array_key_exists('tags', $validated)) {
            $updateData['tags'] = $validated['tags'];
        }
        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        $contact->update($updateData);

        // Update primary contact if requested
        if ($validated['setPrimaryContact'] ?? false) {
            $contact->party->update([
                'primary_contact_id' => $contact->id,
                'last_activity' => now(),
            ]);
        }

        return back()->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy(Request $request, Contact $contact): RedirectResponse
    {
        $this->authorize('delete', $contact);

        $party = $contact->party;

        // If this is the primary contact, clear the party's primary_contact_id
        if ($party->primary_contact_id == $contact->id) {
            $party->update([
                'primary_contact_id' => null,
                'last_activity' => now(),
            ]);
        }

        $contact->delete();

        return back()->with('success', 'Contact deleted successfully.');
    }
}
