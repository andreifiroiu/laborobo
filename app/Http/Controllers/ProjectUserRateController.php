<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectUserRateRequest;
use App\Http\Requests\UpdateProjectUserRateRequest;
use App\Models\Project;
use App\Models\ProjectUserRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectUserRateController extends Controller
{
    /**
     * List project-specific rates for a project.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $rates = ProjectUserRate::forProject($project->id)
            ->with('user')
            ->orderByDesc('effective_date')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($userRates) => $userRates->first());

        $formattedRates = $rates->map(fn (ProjectUserRate $rate) => [
            'id' => (string) $rate->id,
            'userId' => (string) $rate->user_id,
            'userName' => $rate->user?->name ?? 'Unknown',
            'userEmail' => $rate->user?->email ?? '',
            'internalRate' => (float) $rate->internal_rate,
            'billingRate' => (float) $rate->billing_rate,
            'effectiveDate' => $rate->effective_date->format('Y-m-d'),
        ])->values();

        return response()->json([
            'rates' => $formattedRates,
        ]);
    }

    /**
     * Create a project-specific rate override.
     */
    public function store(StoreProjectUserRateRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validated();

        ProjectUserRate::create([
            'project_id' => $project->id,
            'user_id' => $validated['user_id'],
            'internal_rate' => $validated['internal_rate'],
            'billing_rate' => $validated['billing_rate'],
            'effective_date' => $validated['effective_date'],
        ]);

        return back()->with('status', 'Project rate override created successfully.');
    }

    /**
     * Update a project-specific rate override.
     */
    public function update(UpdateProjectUserRateRequest $request, Project $project, ProjectUserRate $rate): RedirectResponse
    {
        $this->authorize('update', $project);

        // Verify the rate belongs to this project
        if ($rate->project_id !== $project->id) {
            abort(403, 'This rate does not belong to the specified project.');
        }

        $validated = $request->validated();

        $rate->update($validated);

        return back()->with('status', 'Project rate override updated successfully.');
    }

    /**
     * Remove a project-specific rate override.
     */
    public function destroy(Request $request, Project $project, ProjectUserRate $rate): RedirectResponse
    {
        $this->authorize('update', $project);

        // Verify the rate belongs to this project
        if ($rate->project_id !== $project->id) {
            abort(403, 'This rate does not belong to the specified project.');
        }

        $rate->delete();

        return back()->with('status', 'Project rate override removed successfully.');
    }
}
