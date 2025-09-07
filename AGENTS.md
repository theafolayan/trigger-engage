# Trigger Engage Guidelines

## Product Vision
Trigger Engage is an open-source, self-hosted, event-driven messaging automation tool. It empowers teams to build and run email and push notification workflows on their own infrastructure.
Workspaces keep contacts, templates, automations, and device tokens isolated, and events trigger those automations through a JSON:API interface.

## Architecture
- **Domain**: Core business logic and aggregates.
- **Services**: Cross-cutting services and integrations (mail, push, queue, etc.).
- **Http**: Controllers, requests, and routes.
- **Middleware**: HTTP middleware.

## Coding Conventions
- PHP 8.3 with strict types.
- Use PHP enums for status values and similar finite sets.
- All endpoints return JSON in the [JSON:API](https://jsonapi.org/) style.
- Follow PSR-12 formatting; prefer early returns.

## TDD Workflow
1. **Red** – write a failing test that describes the desired behavior.
2. **Green** – write the minimal code to make the test pass.
3. **Refactor** – improve the implementation while keeping tests green.
Commit only when tests are green.

## Getting Started
1. `composer install` and `npm install` to fetch dependencies.
2. Copy `.env.example` to `.env`, run `php artisan key:generate`, and configure PostgreSQL and Redis.
3. Run migrations with `php artisan migrate` and seed demo data with `php artisan make:demo`.
4. Start queues with `php artisan horizon` and the scheduler with `php artisan schedule:work`.
5. Optional: configure push notification drivers (Expo or OneSignal) in Filament and register device tokens via the API.
6. Use `php artisan serve` to run the app locally and `php artisan test` to execute the test suite.

## Documentation
- API docs live under `docs/api`.
- Document endpoints with JSON:API request/response examples and note required `Authorization` and `X-Workspace` headers.
- Cross-link related sections for discoverability.
