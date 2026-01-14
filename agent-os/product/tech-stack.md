# Tech Stack

This document defines the complete technical stack for Laborobo, an AI-powered work orchestration platform built on Laravel with React/Inertia.js.

## Framework & Runtime

- **Application Framework:** Laravel 12
- **Language/Runtime:** PHP 8.3+
- **Package Manager:** Composer
- **Authentication:** Fortify
- **Authorization:** Laravel policies and gates

## Frontend

- **JavaScript Framework:** React 18+
- **Bridge Layer:** Inertia.js (connects Laravel backend to React frontend without building an API)
- **UI Component Library:** shadcn/ui (accessible, customizable React components)
- **CSS Framework:** Tailwind CSS v4 (utility-first CSS with Vite plugin)
- **Build Tool:** Vite (fast HMR, optimized production builds)
- **JavaScript Package Manager:** npm

## Database & Storage

- **Primary Database:** MySQL 8.0+ (production and development)
- **ORM/Query Builder:** Eloquent ORM (Laravel's built-in ORM)
- **Migrations:** Laravel migrations for version-controlled schema changes
- **Seeders & Factories:** Laravel seeders and model factories for test data

## AI & Machine Learning

- **AI Integration:** neuron-ai package (existing Laravel package for AI functionality)
- **AI Agent Framework:** Custom-built agent orchestration system with Tool Gateway architecture
- **LLM Provider:** Mainly Anthropic Claude, but other providers such as OpenAI will be also integrated

## Testing & Quality

- **Test Framework:** Pest (modern PHP testing framework with expressive syntax)
- **JavaScript Testing:** TBD (likely Vitest for React components)
- **Code Formatting:** Laravel Pint (Laravel's opinionated PHP code formatter)
- **Static Analysis:** PHPStan with Larastan (catches bugs without running code)
- **JavaScript Linting:** ESLint (code quality and consistency for React)

## Queues & Background Processing

- **Queue System:** Laravel Queues (database driver initially, Redis future consideration)
- **Job Processing:** Laravel queue workers with supervisord or Laravel Horizon
- **Scheduling:** Laravel Task Scheduler for cron-like scheduled tasks

## Deployment & Infrastructure

- **Hosting:** Hetzner (cost-effective European cloud hosting)
- **Deployment Tool:** Laravel Forge (automated deployment, server management, SSL)
- **CI/CD:** Laravel Forge automated deployments from Git
- **Web Server:** Nginx (reverse proxy and static file serving)
- **Process Manager:** PHP-FPM (PHP process manager)

## Third-Party Services

- **Email Service:** Mailgun (transactional email delivery)
- **Error Tracking:** Sentry (error monitoring and performance tracking)
- **File Storage:** Local filesystem initially, S3-compatible storage (planned future addition)
- **Cache Store:** Database initially, Redis (planned future addition for caching and sessions)

## Development Tools

- **Version Control:** Git
- **Local Environment:** Laravel Valet
- **API Documentation:** Scribe
- **Database Management:** TablePlus

## Future Infrastructure Additions

These components are noted for future implementation but not immediate priorities:

- **Redis:** For advanced caching, session management, queue processing, and real-time features
- **S3-Compatible Storage:** For scalable document and artifact storage with CDN distribution
- **Websockets:** For real-time collaboration features (Laravel Reverb or Pusher)
- **Search Engine:** For full-text search capabilities (Meilisearch or Algolia)

## Architecture Patterns

- **Frontend Architecture:** Server-driven SPA using Inertia.js (no separate API needed)
- **State Management:** React hooks and context for client-side state, Inertia for server state
- **API Style:** Inertia controller responses (not RESTful JSON API)
- **Agent Architecture:** Tool Gateway pattern for controlled AI agent operations
- **Work Graph Model:** Domain-driven design with Parties → Projects → Work Orders → Tasks → Deliverables
- **Event Sourcing:** Laravel events for agent actions, state transitions, and audit logging

## Security & Compliance

- **Authentication:** Laravel Sanctum with SPA token-based auth
- **CSRF Protection:** Inertia CSRF token handling built-in
- **Input Validation:** Laravel Form Requests for backend validation
- **XSS Protection:** React's built-in JSX escaping, Laravel Blade escaping
- **Authorization:** Laravel policies for fine-grained permissions
- **Audit Logging:** Custom audit log for all AI agent operations and human approvals

## Notes

- **Incremental Infrastructure:** Redis and S3 are planned additions but not required for initial launch
- **AI Provider Flexibility:** The neuron-ai package provides abstraction; specific LLM providers can be swapped based on performance and cost requirements
