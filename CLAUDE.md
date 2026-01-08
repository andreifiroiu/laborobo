# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application using Livewire v3, Volt (for single-file Livewire components), Laravel Fortify (for authentication), and Livewire Flux (UI components). The project uses Tailwind CSS v4 with Vite for asset building, and Pest for testing. It includes the neuron-ai package for AI functionality.

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
# Format code with Laravel Pint
./vendor/bin/pint

# Format specific files/directories
./vendor/bin/pint app/Http/Controllers
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
# Development build
npm run dev

# Production build
npm run build
```

### Queue Management
```bash
# Process queue jobs
php artisan queue:work

# Listen for queue jobs (auto-reloads on code changes)
php artisan queue:listen --tries=1
```

## Architecture

### Authentication & Authorization
- Uses **Laravel Fortify** for authentication (registration, login, password reset, email verification, two-factor authentication)
- Authentication views and logic are implemented using **Livewire Volt** components in `resources/views/livewire/`
- Two-factor authentication is configured and available in settings
- Fortify configuration is in `config/fortify.php`
- Custom Fortify actions are in `app/Actions/Fortify/`
- FortifyServiceProvider customizes view responses in `app/Providers/FortifyServiceProvider.php`

### Frontend Architecture
- **Livewire v3** with **Volt** for reactive components (single-file components using PHP)
- **Livewire Flux** provides the UI component library (custom blade components in `resources/views/flux/`)
- Volt components are registered in routes via `Volt::route()` in `routes/web.php`
- Standard Livewire components are in `app/Livewire/`
- Volt components (blade-only) are in `resources/views/livewire/`
- **Tailwind CSS v4** for styling via Vite plugin
- Asset pipeline managed by **Vite** with Laravel plugin

### Routing
- Web routes defined in `routes/web.php`
- Volt routes use `Volt::route()` method for single-file components
- Authentication and settings routes are grouped with appropriate middleware

### Database
- Default configuration uses **SQLite** (see .env.example)
- Database file typically at `database/database.sqlite`
- Migrations in `database/migrations/`
- Seeders in `database/seeders/`
- Factories in `database/factories/`

### Testing with Pest
- Test configuration in `tests/Pest.php`
- Feature tests automatically use `RefreshDatabase` trait
- Feature tests in `tests/Feature/`
- Unit tests in `tests/Unit/`
- Tests use Pest's expect syntax
- Test environment configured in `phpunit.xml` (uses in-memory SQLite)

### Layouts & Components
- Main app layout: `resources/views/components/layouts/app.blade.php`
- Authentication layouts in `resources/views/components/layouts/auth/`
- Sidebar component: `resources/views/components/layouts/app/sidebar.blade.php`
- Header component: `resources/views/components/layouts/app/header.blade.php`
- Settings layout: `resources/views/components/settings/layout.blade.php`

### Service Providers
- `AppServiceProvider`: Main application service provider
- `FortifyServiceProvider`: Customizes Fortify authentication views and responses
- `VoltServiceProvider`: Registers Volt routes
- Providers registered in `bootstrap/providers.php`

### Configuration
- Application bootstrap in `bootstrap/app.php` (Laravel 12 structure)
- Configuration files in `config/`
- Environment variables in `.env` (use `.env.example` as template)
- Queue connection defaults to `database`
- Cache store defaults to `database`
- Session driver defaults to `database`

## Important Patterns

### Livewire Volt Components
Volt components are single-file PHP components that combine logic and views. They're typically used for simpler components and are defined directly in blade files with `@volt` directives.

### Fortify Integration
When modifying authentication flows, be aware that Fortify handles the backend logic while Livewire provides the frontend. Custom logic should be added via Fortify actions in `app/Actions/Fortify/`.

### Queue Jobs
The application uses database queues by default. Queue jobs should be processed via `php artisan queue:work` or `queue:listen`. The `composer dev` command automatically starts a queue listener.

### Tailwind CSS v4
Uses the new Tailwind v4 via Vite plugin. Tailwind configuration is handled differently than v3 - check Vite config for setup details.

## Agent-OS Integration
This project includes agent-os configuration in `agent-os/` directory with standards for backend, frontend, global, and testing practices. The configuration uses Claude Code commands and subagents.
