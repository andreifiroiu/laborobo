# Milestone 2: Multi-Organization Support

This milestone implements complete multi-tenancy using the Jurager/teams package, allowing users to belong to multiple organizations with separate data isolation.

## Overview

**Goal**: Implement full multi-organization support with team switching, invitations, and proper data isolation.

**Time Estimate**: 1 week

**Key Features**:
- Users can belong to multiple organizations
- Complete data isolation between organizations
- Team switching
- Team creation and management
- Member invitations and roles
- Current team context throughout the app

## Architecture Overview

### Data Isolation Strategy

Each organization (team) will have its own isolated data:
- Projects belong to teams
- Work Orders belong to teams
- Tasks, Deliverables, SOPs all scoped to teams
- Users can switch between teams via UI
- Current team stored in session

### Package Choice: Jurager/teams

This package provides:
- Team model with ownership
- Team membership with roles
- Current team context
- Automatic scoping for models
- Team switching middleware

## Step 1: Install and Configure Jurager/teams

### Install Package
The package is already installed

### Publish Configuration and Migrations

### Publish Configuration and Migrations

Migrations are already published and run.


### Configure the Package

**config/teams.php**:
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Teams Table Name
    |--------------------------------------------------------------------------
    */
    'teams_table' => 'teams',

    /*
    |--------------------------------------------------------------------------
    | Team Members Table Name
    |--------------------------------------------------------------------------
    */
    'team_members_table' => 'team_members',

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    */
    'user_model' => App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Team Model
    |--------------------------------------------------------------------------
    */
    'team_model' => App\Models\Team::class,

    /*
    |--------------------------------------------------------------------------
    | Available Roles
    |--------------------------------------------------------------------------
    | Define the roles available in your application
    */
    'roles' => [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'member' => 'Member',
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'team' => \Jurager\Teams\Middleware\TeamMiddleware::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => 'teams',
        'middleware' => ['web', 'auth'],
    ],
];
```

## Step 2: Update User Model

**app/Models/User.php**:
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Jurager\Teams\Traits\HasTeams;

class User extends Authenticatable
{
    use Notifiable, HasTeams;

    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'language',
        'current_team_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's display name for Doqio
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get user's timezone or default
     */
    public function getTimezoneAttribute($value): string
    {
        return $value ?? 'UTC';
    }

    /**
     * Get user's language or default
     */
    public function getLanguageAttribute($value): string
    {
        return $value ?? 'en-US';
    }
}
```

## Step 3: Create Team Model

**app/Models/Team.php**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Jurager\Teams\Traits\HasMembers;

class Team extends Model
{
    use HasFactory, HasMembers;

    protected $fillable = [
        'name',
        'slug',
        'owner_id',
        'plan',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Get the owner of the team
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Generate a unique slug for the team
     */
    public static function generateSlug(string $name): string
    {
        $slug = \Illuminate\Support\Str::slug($name);
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = \Illuminate\Support\Str::slug($name) . '-' . $count;
            $count++;
        }

        return $slug;
    }

    /**
     * Get team member count
     */
    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Scope to user's teams
     */
    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }
}
```

## Step 4: Update Database Migrations

### Modify Users Table Migration

Update the existing users migration or create a new one:

```bash
php artisan make:migration add_team_fields_to_users_table
```

**database/migrations/xxxx_add_team_fields_to_users_table.php**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_team_id')
                ->nullable()
                ->after('language')
                ->constrained('teams')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_team_id']);
            $table->dropColumn('current_team_id');
        });
    }
};
```

### Customize Teams Migration

After publishing, update the teams migration:

**database/migrations/xxxx_create_teams_table.php**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->enum('plan', ['Starter', 'Team', 'Business'])->default('Starter');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'admin', 'member'])->default('member');
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
    }
};
```

### Run Migrations

```bash
php artisan migrate
```

## Step 5: Create Team-Scoped Base Model

Create a trait for models that belong to teams:

**app/Models/Concerns/BelongsToTeam.php**:
```php
<?php

namespace App\Models\Concerns;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTeam
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToTeam(): void
    {
        // Automatically set team_id when creating
        static::creating(function ($model) {
            if (!$model->team_id && auth()->check()) {
                $model->team_id = auth()->user()->currentTeam?->id;
            }
        });

        // Global scope to filter by current team
        static::addGlobalScope('team', function (Builder $builder) {
            if (auth()->check() && auth()->user()->currentTeam) {
                $builder->where('team_id', auth()->user()->currentTeam->id);
            }
        });
    }

    /**
     * Get the team that owns the model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope query to specific team
     */
    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope query without team restriction
     */
    public function scopeWithoutTeamScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('team');
    }
}
```

## Step 6: Create Team Management Controllers

### TeamController

**app/Http/Controllers/TeamController.php**:
```php
<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TeamController extends Controller
{
    /**
     * Display teams management page
     */
    public function index()
    {
        $teams = Auth::user()->teams()->with('owner')->get()->map(function ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'plan' => $team->plan,
                'role' => Auth::user()->roleOn($team),
                'memberCount' => $team->memberCount,
                'lastActive' => $team->updated_at->toISOString(),
                'isCurrent' => $team->id === Auth::user()->current_team_id,
                'isOwner' => $team->owner_id === Auth::id(),
            ];
        });

        return Inertia::render('Teams/Index', [
            'teams' => $teams,
        ]);
    }

    /**
     * Store a new team
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team = Team::create([
            'name' => $validated['name'],
            'slug' => Team::generateSlug($validated['name']),
            'owner_id' => Auth::id(),
            'plan' => 'Starter',
        ]);

        // Add creator as owner
        $team->addMember(Auth::user(), 'owner');

        // Switch to new team
        Auth::user()->switchTeam($team);

        return redirect()->route('teams.index')->with('message', 'Team created successfully!');
    }

    /**
     * Switch to a different team
     */
    public function switch(Team $team)
    {
        // Verify user is a member
        if (!Auth::user()->belongsToTeam($team)) {
            abort(403, 'You do not belong to this team.');
        }

        Auth::user()->switchTeam($team);

        return redirect()->back()->with('message', 'Switched to ' . $team->name);
    }

    /**
     * Update team details
     */
    public function update(Request $request, Team $team)
    {
        // Verify user is owner or admin
        if (!Auth::user()->ownsTeam($team) && Auth::user()->roleOn($team) !== 'admin') {
            abort(403, 'You do not have permission to update this team.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team->update([
            'name' => $validated['name'],
            'slug' => Team::generateSlug($validated['name']),
        ]);

        return redirect()->back()->with('message', 'Team updated successfully!');
    }

    /**
     * Delete team
     */
    public function destroy(Team $team)
    {
        // Only owner can delete
        if (!Auth::user()->ownsTeam($team)) {
            abort(403, 'Only the team owner can delete the team.');
        }

        // Can't delete if it's the user's only team
        if (Auth::user()->teams()->count() === 1) {
            return redirect()->back()->withErrors(['team' => 'You must have at least one team.']);
        }

        // Switch to another team first
        $otherTeam = Auth::user()->teams()->where('id', '!=', $team->id)->first();
        Auth::user()->switchTeam($otherTeam);

        $team->delete();

        return redirect()->route('teams.index')->with('message', 'Team deleted successfully!');
    }
}
```

### TeamMemberController

**app/Http/Controllers/TeamMemberController.php**:
```php
<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TeamMemberController extends Controller
{
    /**
     * Display team members
     */
    public function index(Team $team)
    {
        // Verify user is a member
        if (!Auth::user()->belongsToTeam($team)) {
            abort(403);
        }

        $members = $team->members()->get()->map(function ($user) use ($team) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roleOn($team),
                'joinedAt' => $user->pivot->created_at->toISOString(),
            ];
        });

        return response()->json(['members' => $members]);
    }

    /**
     * Invite a new member
     */
    public function store(Request $request, Team $team)
    {
        // Verify user is owner or admin
        if (!Auth::user()->ownsTeam($team) && Auth::user()->roleOn($team) !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', Rule::in(['admin', 'member'])],
        ]);

        // Find or create user
        $user = User::firstOrCreate(
            ['email' => $validated['email']],
            ['name' => explode('@', $validated['email'])[0]]
        );

        // Check if already a member
        if ($team->hasMember($user)) {
            return redirect()->back()->withErrors(['email' => 'This user is already a team member.']);
        }

        // Add member
        $team->addMember($user, $validated['role']);

        // TODO: Send invitation email

        return redirect()->back()->with('message', 'Member invited successfully!');
    }

    /**
     * Update member role
     */
    public function update(Request $request, Team $team, User $user)
    {
        // Verify user is owner
        if (!Auth::user()->ownsTeam($team)) {
            abort(403, 'Only the team owner can change member roles.');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'member'])],
        ]);

        // Can't change owner's role
        if ($team->owner_id === $user->id) {
            return redirect()->back()->withErrors(['role' => 'Cannot change owner role.']);
        }

        $team->updateMemberRole($user, $validated['role']);

        return redirect()->back()->with('message', 'Member role updated successfully!');
    }

    /**
     * Remove member from team
     */
    public function destroy(Team $team, User $user)
    {
        // Verify user is owner or removing themselves
        if (!Auth::user()->ownsTeam($team) && $user->id !== Auth::id()) {
            abort(403);
        }

        // Can't remove owner
        if ($team->owner_id === $user->id) {
            return redirect()->back()->withErrors(['member' => 'Cannot remove team owner.']);
        }

        $team->removeMember($user);

        // If user removed themselves, switch to another team
        if ($user->id === Auth::id()) {
            $otherTeam = Auth::user()->teams()->first();
            Auth::user()->switchTeam($otherTeam);
        }

        return redirect()->back()->with('message', 'Member removed successfully!');
    }
}
```

## Step 7: Update Routes

**routes/web.php**:
```php
<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('today');
});

Route::middleware('auth')->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Team management routes
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::patch('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{team}/switch', [TeamController::class, 'switch'])->name('teams.switch');

    // Team member routes
    Route::get('/teams/{team}/members', [TeamMemberController::class, 'index'])->name('teams.members.index');
    Route::post('/teams/{team}/members', [TeamMemberController::class, 'store'])->name('teams.members.store');
    Route::patch('/teams/{team}/members/{user}', [TeamMemberController::class, 'update'])->name('teams.members.update');
    Route::delete('/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('teams.members.destroy');

    // Application routes (all require current team)
    Route::middleware(['team'])->group(function () {
        Route::get('/today', fn() => Inertia::render('Today/Index'))->name('today');
        Route::get('/work', fn() => Inertia::render('Work/Index'))->name('work');
        Route::get('/inbox', fn() => Inertia::render('Inbox/Index'))->name('inbox');
        Route::get('/playbooks', fn() => Inertia::render('Playbooks/Index'))->name('playbooks');
        Route::get('/directory', fn() => Inertia::render('Directory/Index'))->name('directory');
        Route::get('/reports', fn() => Inertia::render('Reports/Index'))->name('reports');
        Route::get('/settings', fn() => Inertia::render('Settings/Index'))->name('settings');
    });
});

require __DIR__.'/auth.php';
```

## Step 8: Update HandleInertiaRequests

**app/Http/Middleware/HandleInertiaRequests.php**:
```php
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();
        
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => (string) $user->id,
                    'displayName' => $user->displayName,
                    'email' => $user->email,
                    'timezone' => $user->timezone,
                    'language' => $user->language,
                    'name' => $user->name,
                    'email_verified_at' => $user->email_verified_at,
                ] : null,
            ],
            'currentOrganization' => $user && $user->currentTeam ? [
                'id' => (string) $user->currentTeam->id,
                'name' => $user->currentTeam->name,
                'slug' => $user->currentTeam->slug,
                'plan' => $user->currentTeam->plan,
                'role' => $user->roleOn($user->currentTeam),
                'memberCount' => $user->currentTeam->memberCount,
                'lastActive' => $user->currentTeam->updated_at->toISOString(),
                'isCurrent' => true,
            ] : null,
            'organizations' => $user ? $user->teams->map(fn($team) => [
                'id' => (string) $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'plan' => $team->plan,
                'role' => $user->roleOn($team),
                'memberCount' => $team->memberCount,
                'lastActive' => $team->updated_at->toISOString(),
                'isCurrent' => $team->id === $user->current_team_id,
            ])->toArray() : [],
        ];
    }
}
```

## Step 9: Update AppLayout to Use Real Team Data

**resources/js/Layouts/AppLayout.tsx**:
```tsx
import React, { PropsWithChildren } from 'react'
import { usePage, router } from '@inertiajs/react'
import { PageProps } from '@/types'
import { NavigationItem } from '@/types/doqio'
import { AppShell } from '@/Components/Shell'
import { Home, Briefcase, Inbox, BookOpen, Users, BarChart3, Settings } from 'lucide-react'

export default function AppLayout({ children }: PropsWithChildren) {
  const { auth, currentOrganization, organizations } = usePage<PageProps>().props
  const currentPath = window.location.pathname

  const navigationItems: NavigationItem[] = [
    {
      label: 'Today',
      href: route('today'),
      icon: Home,
      isActive: currentPath === route('today'),
    },
    {
      label: 'Work',
      href: route('work'),
      icon: Briefcase,
      isActive: currentPath.startsWith('/work'),
    },
    {
      label: 'Inbox',
      href: route('inbox'),
      icon: Inbox,
      isActive: currentPath === route('inbox'),
      badge: 0,
    },
    {
      label: 'Playbooks',
      href: route('playbooks'),
      icon: BookOpen,
      isActive: currentPath.startsWith('/playbooks'),
    },
    {
      label: 'Directory',
      href: route('directory'),
      icon: Users,
      isActive: currentPath.startsWith('/directory'),
    },
    {
      label: 'Reports',
      href: route('reports'),
      icon: BarChart3,
      isActive: currentPath.startsWith('/reports'),
    },
    {
      label: 'Settings',
      href: route('settings'),
      icon: Settings,
      isActive: currentPath.startsWith('/settings'),
    },
  ]

  const handleSwitchOrganization = (orgId: string) => {
    router.post(route('teams.switch', orgId), {}, {
      preserveState: false,
      preserveScroll: true,
    })
  }

  return (
    <AppShell
      navigationItems={navigationItems}
      user={auth.user}
      organizations={organizations || []}
      currentOrganization={currentOrganization}
      onNavigate={(href) => router.visit(href)}
      onSwitchOrganization={handleSwitchOrganization}
      onOpenProfile={() => router.visit(route('profile.edit'))}
      onLogout={() => router.post(route('logout'))}
    >
      {children}
    </AppShell>
  )
}
```

## Step 10: Create Team Management UI

### Teams Index Page

**resources/js/Pages/Teams/Index.tsx**:
```tsx
import React, { useState } from 'react'
import AppLayout from '@/Layouts/AppLayout'
import { PageProps } from '@/types'
import { Organization } from '@/types/doqio'
import { Head, router, useForm } from '@inertiajs/react'
import { Plus, Users, Settings, Trash2 } from 'lucide-react'

interface TeamsPageProps extends PageProps {
  teams: Organization[]
}

export default function TeamsIndex({ teams }: TeamsPageProps) {
  const [showCreateModal, setShowCreateModal] = useState(false)
  
  const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
  })

  const handleCreateTeam = (e: React.FormEvent) => {
    e.preventDefault()
    post(route('teams.store'), {
      onSuccess: () => {
        reset()
        setShowCreateModal(false)
      },
    })
  }

  return (
    <>
      <Head title="Teams" />
      <div className="p-8">
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-4xl font-semibold text-neutral-900 dark:text-neutral-100">
              Teams
            </h1>
            <p className="mt-2 text-neutral-600 dark:text-neutral-400">
              Manage your organizations and team memberships
            </p>
          </div>
          <button
            onClick={() => setShowCreateModal(true)}
            className="flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
          >
            <Plus className="w-4 h-4" />
            Create Team
          </button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {teams.map((team) => (
            <div
              key={team.id}
              className="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6"
            >
              <div className="flex items-start justify-between mb-4">
                <div>
                  <h3 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                    {team.name}
                  </h3>
                  <p className="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                    {team.role} • {team.plan} Plan
                  </p>
                </div>
                {team.isCurrent && (
                  <span className="px-2 py-1 text-xs font-semibold rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                    Current
                  </span>
                )}
              </div>

              <div className="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400 mb-4">
                <Users className="w-4 h-4" />
                <span>{team.memberCount} members</span>
              </div>

              <div className="flex gap-2">
                {!team.isCurrent && (
                  <button
                    onClick={() => router.post(route('teams.switch', team.id))}
                    className="flex-1 px-3 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300 rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition-colors text-sm font-medium"
                  >
                    Switch to
                  </button>
                )}
                {(team.role === 'Owner' || team.role === 'Admin') && (
                  <button
                    onClick={() => router.visit(`/teams/${team.id}/settings`)}
                    className="px-3 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300 rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition-colors"
                  >
                    <Settings className="w-4 h-4" />
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Create Team Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white dark:bg-neutral-800 rounded-lg max-w-md w-full p-6">
            <h2 className="text-2xl font-semibold text-neutral-900 dark:text-neutral-100 mb-4">
              Create New Team
            </h2>
            <form onSubmit={handleCreateTeam}>
              <div className="mb-4">
                <label className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                  Team Name
                </label>
                <input
                  type="text"
                  value={data.name}
                  onChange={(e) => setData('name', e.target.value)}
                  className="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-neutral-900 dark:text-neutral-100"
                  placeholder="Acme Agency"
                  autoFocus
                />
                {errors.name && (
                  <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                )}
              </div>
              <div className="flex gap-3 justify-end">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="px-4 py-2 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 rounded-lg transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={processing}
                  className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50"
                >
                  {processing ? 'Creating...' : 'Create Team'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  )
}

TeamsIndex.layout = (page: React.ReactElement) => <AppLayout>{page}</AppLayout>
```

## Step 11: Create Database Seeder

**database/seeders/TeamSeeder.php**:
```php
<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'timezone' => 'America/New_York',
                'language' => 'en-US',
            ]
        );

        // Create default team
        $team = Team::create([
            'name' => 'My First Team',
            'slug' => 'my-first-team',
            'owner_id' => $user->id,
            'plan' => 'Starter',
        ]);

        // Add user as owner
        $team->addMember($user, 'owner');

        // Set as current team
        $user->update(['current_team_id' => $team->id]);

        // Create a second team for testing switching
        $team2 = Team::create([
            'name' => 'Development Team',
            'slug' => 'development-team',
            'owner_id' => $user->id,
            'plan' => 'Team',
        ]);

        $team2->addMember($user, 'owner');
    }
}
```

Update **database/seeders/DatabaseSeeder.php**:
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TeamSeeder::class,
        ]);
    }
}
```

## Step 12: Create Team Middleware (if not provided by package)

**app/Http/Middleware/EnsureUserHasTeam.php**:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasTeam
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // If user has no teams, redirect to create one
        if ($request->user()->teams()->count() === 0) {
            return redirect()->route('teams.index')
                ->with('error', 'Please create a team to continue.');
        }

        // If user has teams but no current team, set the first one
        if (!$request->user()->current_team_id) {
            $firstTeam = $request->user()->teams()->first();
            $request->user()->switchTeam($firstTeam);
        }

        return $next($request);
    }
}
```

Register in **bootstrap/app.php**:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'team' => \App\Http\Middleware\EnsureUserHasTeam::class,
    ]);
})
```

## Step 13: Run and Test

### Fresh Install

```bash
# Fresh migrations
php artisan migrate:fresh --seed

# Start servers
php artisan serve
npm run dev
```

### Testing Checklist

- [ ] Register a new user
- [ ] User automatically gets a default team
- [ ] Navigate to /teams page
- [ ] Create a second team
- [ ] Team appears in user menu dropdown
- [ ] Switch between teams from user menu
- [ ] Current team indicator updates
- [ ] All application pages show current team context
- [ ] Invite a member to team (basic functionality)
- [ ] Update team name
- [ ] Team settings are scoped correctly

### Test Team Switching

1. Login as test user
2. Go to `/teams`
3. Create new team "Test Agency"
4. Click user menu - see both teams
5. Click on "Test Agency" - switch to it
6. Verify current team badge moved
7. Navigate to `/today` - still on correct team
8. Switch back to first team

## Step 14: Add Team Context to Page Components

Update any page that displays team-specific data:

**resources/js/Pages/Today/Index.tsx**:
```tsx
import React from 'react'
import AppLayout from '@/Layouts/AppLayout'
import { PageProps } from '@/types'
import { Head, usePage } from '@inertiajs/react'

export default function TodayIndex({ auth }: PageProps) {
  const { currentOrganization } = usePage<PageProps>().props

  return (
    <>
      <Head title="Today" />
      <div className="p-8">
        <div className="mb-6">
          <h1 className="text-4xl font-semibold text-neutral-900 dark:text-neutral-100">
            Today
          </h1>
          <p className="mt-2 text-neutral-600 dark:text-neutral-400">
            {currentOrganization?.name}
          </p>
        </div>

        <div className="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
          <p className="text-sm text-neutral-600 dark:text-neutral-400">
            Welcome to <span className="font-semibold">{currentOrganization?.name}</span>!
          </p>
          <p className="text-sm text-neutral-500 dark:text-neutral-500 mt-2">
            You are logged in as <span className="font-semibold">{auth.user.displayName}</span>
          </p>
          <p className="text-sm text-neutral-500 dark:text-neutral-500 mt-1">
            Your role: <span className="font-semibold">{currentOrganization?.role}</span>
          </p>
        </div>
      </div>
    </>
  )
}

TodayIndex.layout = (page: React.ReactElement) => <AppLayout>{page}</AppLayout>
```

## Architecture Summary

### Data Flow

1. **User logs in** → Has many teams via `team_members` pivot
2. **User has current_team_id** → Active team stored in session
3. **User switches teams** → Updates `current_team_id`, refreshes page
4. **All models use BelongsToTeam trait** → Automatic scoping by team
5. **HandleInertiaRequests shares** → Current team + all user's teams to frontend
6. **Frontend displays** → Current team in user menu, all teams for switching

### Team Hierarchy

```
User
├── Team 1 (Owner)
│   ├── Projects
│   ├── Work Orders
│   └── Members
├── Team 2 (Admin)
│   ├── Projects
│   └── Members
└── Team 3 (Member)
    └── Projects
```

### Security Model

- **Ownership**: Team owners can manage all aspects
- **Admin**: Can manage members, projects, settings
- **Member**: Can view and edit assigned work
- **Data Isolation**: Global scopes ensure no cross-team data leaks

## Next Steps

With multi-organization support complete:

1. **Milestone 3**: Add multi-language support
1. **Milestone 4**: Build Today section with team-scoped data
2. **Milestone 5**: Implement Work management (Projects, Work Orders, Tasks)
3. **Milestone 6**: Create Inbox with team-wide approvals
4. All future features will automatically be team-scoped

## Troubleshooting

### Team Switching Not Working

- Check `current_team_id` updates in database
- Verify `HandleInertiaRequests` loads correct team
- Clear browser cache/cookies

### Global Scope Issues

- Use `withoutTeamScope()` for admin queries
- Ensure `team_id` column exists on models
- Check migration ran successfully

### User Has No Teams

- Seeder should create default team
- Registration should auto-create team
- Middleware redirects to team creation

