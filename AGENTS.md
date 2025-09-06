# Trigger Engage Guidelines

## Product Vision
Trigger Engage is an open-source, self-hosted, event-driven email automation tool. It empowers teams to build and run email workflows on their own infrastructure.

## Architecture
- **Domain**: Core business logic and aggregates.
- **Services**: Cross-cutting services and integrations (mail, queue, etc.).
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
