# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application using React 19, Inertia.js, TypeScript, and Radix UI for the frontend. The project uses Laravel Fortify for authentication, Tailwind CSS v4 with Vite for asset building, and Pest for testing. It includes the neuron-ai package (planned) for AI agent functionality.

## Development Commands

### Initial Setup
```bash
composer setup
```
This runs the full setup: installs PHP dependencies, creates .env if needed, generates app key, runs migrations, installs npm packages, and builds assets.

### Development Server
```bash
composer dev
```
Starts a concurrent development environment with:
- PHP artisan serve (server)
- Queue listener
- Pail log viewer
- Vite dev server for hot module replacement

### Testing
```bash
# Run all tests
composer test

# Run specific test file
php artisan test tests/Feature/Auth/AuthenticationTest.php

# Run tests with Pest directly
./vendor/bin/pest

# Run specific test by name
./vendor/bin/pest --filter="user can login"
```

### Code Quality
```bash
# Format PHP code with Laravel Pint
./vendor/bin/pint

# Format specific files/directories
./vendor/bin/pint app/Http/Controllers

# Run PHPStan static analysis
./vendor/bin/phpstan analyse

# Lint TypeScript/React code
npm run lint
```

### Database
```bash
# Run migrations
php artisan migrate

# Fresh migration with seed
php artisan migrate:fresh --seed

# Rollback
php artisan migrate:rollback
```

### Assets
```bash
# Development build with HMR
npm run dev

# Production build
npm run build

# Type check TypeScript
npm run type-check
```

### Queue Management
```bash
# Process queue jobs
php artisan queue:work

# Listen for queue jobs (auto-reloads on code changes)
php artisan queue:listen --tries=1
```

## Architecture

### Authentication and Authorization
- Uses **Laravel Fortify** for authentication (registration, login, password reset, email verification, two-factor authentication)
- Authentication views are implemented using **React/Inertia** components in `resources/js/pages/auth/`
- Two-factor authentication is configured and available in account settings
- Fortify configuration is in `config/fortify.php`
- Custom Fortify actions are in `app/Actions/Fortify/`
- FortifyServiceProvider customizes view responses in `app/Providers/FortifyServiceProvider.php`

### Frontend Architecture
- **React 19** with **TypeScript** for type-safe component development
- **Inertia.js** connects Laravel backend to React frontend without building a separate API
- **Radix UI** primitives provide accessible, unstyled components for custom styling
- **TanStack Query** for server state management and data fetching patterns
- **Tailwind CSS v4** for styling via Vite plugin
- Asset pipeline managed by **Vite** with Laravel plugin and HMR support

### Frontend File Structure
- React pages: `resources/js/pages/` (maps to Inertia routes)
- Reusable components: `resources/js/components/`
- UI primitives: `resources/js/components/ui/` (Radix-based)
- TypeScript types: `resources/js/types/`
- Hooks: `resources/js/hooks/`
- Layouts: `resources/js/layouts/`

### Routing
- Web routes defined in `routes/web.php`
- Inertia routes return React page components via `Inertia::render()`
- Authentication and settings routes are grouped with appropriate middleware

### Database
- **MySQL 8.0+** for both development and production (for parity)
- Migrations in `database/migrations/`
- Seeders in `database/seeders/`
- Factories in `database/factories/`

### Testing with Pest
- Test configuration in `tests/Pest.php`
- Feature tests automatically use `RefreshDatabase` trait
- Feature tests in `tests/Feature/`
- Unit tests in `tests/Unit/`
- Tests use Pest's expect syntax
- Test environment configured in `phpunit.xml`

### Layouts and Components
- Main app layout: `resources/js/layouts/app-layout.tsx`
- Authentication layout: `resources/js/layouts/auth-layout.tsx`
- Sidebar component: `resources/js/components/app-sidebar.tsx`
- Header component: `resources/js/components/app-header.tsx`

### Service Providers
- `AppServiceProvider`: Main application service provider
- `FortifyServiceProvider`: Customizes Fortify authentication views and responses
- Providers registered in `bootstrap/providers.php`

### Configuration
- Application bootstrap in `bootstrap/app.php` (Laravel 12 structure)
- Configuration files in `config/`
- Environment variables in `.env` (use `.env.example` as template)
- Queue connection defaults to `database`
- Cache store defaults to `database`
- Session driver defaults to `database`

## Important Patterns

### Inertia.js Page Components
Pages are React components that receive props from Laravel controllers. They're located in `resources/js/pages/` and map directly to Inertia routes.

```tsx
// Example page component
export default function Today({ tasks, approvals }: TodayProps) {
  return (
    <AppLayout>
      <h1>Today</h1>
      {/* ... */}
    </AppLayout>
  );
}
```

### Fortify Integration
When modifying authentication flows, be aware that Fortify handles the backend logic while React/Inertia provides the frontend. Custom logic should be added via Fortify actions in `app/Actions/Fortify/`.

### Component Patterns
- Use Radix UI primitives for accessible interactive components
- Style with Tailwind CSS utility classes
- Keep components small and focused
- Use TypeScript interfaces for all props

### Queue Jobs
The application uses database queues by default. Queue jobs should be processed via `php artisan queue:work` or `queue:listen`. The `composer dev` command automatically starts a queue listener.

### Tailwind CSS v4
Uses the new Tailwind v4 via Vite plugin. Configuration is in `tailwind.config.js` and custom theme tokens in CSS files.

## Key Models

The application implements a work graph structure:
- **Party**: Clients, vendors, or other external entities
- **Project**: Top-level container for work
- **WorkOrder**: Scoped work items with budgets and deliverables
- **Task**: Individual action items within work orders
- **Deliverable**: Output artifacts tied to tasks or work orders

Supporting models include:
- **Playbook**: SOP templates with checklists and validation
- **InboxItem**: Centralized approval queue
- **AIAgent/AgentConfiguration**: AI agent settings and activity logging
- **CommunicationThread/Message**: Contextual conversations tied to work items

## Agent-OS Integration

This project includes agent-os configuration in `agent-os/` directory with standards for backend, frontend, global, and testing practices. The configuration uses Claude Code commands and subagents.

Key documentation:
- Product mission: `agent-os/product/mission.md`
- Development roadmap: `agent-os/product/roadmap.md`
- Tech stack: `agent-os/product/tech-stack.md`
- Coding standards: `agent-os/standards/`
