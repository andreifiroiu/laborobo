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
            'auth' => [
                'user' => $request->user() ? [
                    ...$request->user()->toArray(),
                    'timezone' => $request->user()->timezone ?? 'UTC',
                    'language' => $request->user()->language ?? 'en',
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',

            // Mock organization data (Milestone 2 will make this real)
            'currentOrganization' => $request->user() ? [
                'id' => 1,
                'name' => $request->user()->name . "'s Organization",
                'slug' => 'default',
                'user_id' => $request->user()->id,
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ] : null,

            'organizations' => $request->user() ? [
                [
                    'id' => 1,
                    'name' => $request->user()->name . "'s Organization",
                    'slug' => 'default',
                    'user_id' => $request->user()->id,
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString(),
                ],
                [
                    'id' => 2,
                    'name' => 'Demo Organization',
                    'slug' => 'demo',
                    'user_id' => $request->user()->id,
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString(),
                ],
            ] : [],
        ];
    }
}
