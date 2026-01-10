<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'locale' => app()->getLocale(),
            'availableLocales' => config('app.available_locales'),
            'auth' => [
                'user' => $request->user() ? [
                    ...$request->user()->toArray(),
                    'timezone' => $request->user()->timezone ?? 'UTC',
                    'language' => $request->user()->language ?? 'en',
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',

            // Real organization/team data
            'currentOrganization' => $request->user() && $request->user()->current_team_id ? [
                'id' => $request->user()->currentTeam->id,
                'name' => $request->user()->currentTeam->name,
                'slug' => $request->user()->currentTeam->slug ?? 'team-' . $request->user()->currentTeam->id,
                'user_id' => $request->user()->currentTeam->user_id,
                'created_at' => $request->user()->currentTeam->created_at->toISOString(),
                'updated_at' => $request->user()->currentTeam->updated_at->toISOString(),
            ] : null,

            'organizations' => $request->user() ? $request->user()->allTeams()->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'slug' => $team->slug ?? 'team-' . $team->id,
                    'user_id' => $team->user_id,
                    'created_at' => $team->created_at->toISOString(),
                    'updated_at' => $team->updated_at->toISOString(),
                ];
            })->toArray() : [],
        ];
    }
}
