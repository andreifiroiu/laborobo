<?php

namespace App\Http\Controllers\Directory;

use App\Enums\PartyType;
use App\Http\Controllers\Controller;
use App\Models\Party;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartyController extends Controller
{
    /**
     * Store a newly created party in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', 'string', Rule::in(['client', 'vendor', 'partner', 'internal-department'])],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'status' => 'nullable|string|in:active,inactive',
            'primaryContactId' => 'nullable|exists:contacts,id',
        ]);

        $user = $request->user();
        $team = $user->currentTeam;

        $party = Party::create([
            'team_id' => $team->id,
            'name' => $validated['name'],
            'type' => PartyType::from($validated['type']),
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'status' => $validated['status'] ?? 'active',
            'primary_contact_id' => $validated['primaryContactId'] ?? null,
            'last_activity' => now(),
        ]);

        return back()->with('success', 'Party created successfully.');
    }

    /**
     * Update the specified party in storage.
     */
    public function update(Request $request, Party $party): RedirectResponse
    {
        $this->authorize('update', $party);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => ['sometimes', 'required', 'string', Rule::in(['client', 'vendor', 'partner', 'internal-department'])],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'status' => 'nullable|string|in:active,inactive',
            'primaryContactId' => 'nullable|exists:contacts,id',
        ]);

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (isset($validated['type'])) {
            $updateData['type'] = PartyType::from($validated['type']);
        }
        if (array_key_exists('email', $validated)) {
            $updateData['email'] = $validated['email'];
        }
        if (array_key_exists('phone', $validated)) {
            $updateData['phone'] = $validated['phone'];
        }
        if (array_key_exists('website', $validated)) {
            $updateData['website'] = $validated['website'];
        }
        if (array_key_exists('address', $validated)) {
            $updateData['address'] = $validated['address'];
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
        if (array_key_exists('primaryContactId', $validated)) {
            $updateData['primary_contact_id'] = $validated['primaryContactId'];
        }

        $updateData['last_activity'] = now();

        $party->update($updateData);

        return back()->with('success', 'Party updated successfully.');
    }

    /**
     * Remove the specified party from storage.
     */
    public function destroy(Request $request, Party $party): RedirectResponse
    {
        $this->authorize('delete', $party);

        // Check if party has any contacts
        if ($party->contacts()->exists()) {
            return back()->withErrors(['party' => 'Cannot delete party with existing contacts. Please delete or reassign contacts first.']);
        }

        // Check if party has any projects
        if ($party->projects()->exists()) {
            return back()->withErrors(['party' => 'Cannot delete party with existing projects. Please archive or reassign projects first.']);
        }

        $party->delete();

        return back()->with('success', 'Party deleted successfully.');
    }
}
