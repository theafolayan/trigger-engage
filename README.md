# Trigger Engage

Trigger Engage is an open-source, self-hosted, event-driven email automation tool built on Laravel.
It lets you define reusable email workflows that react to arbitrary events and send templated messages.
Workspaces isolate contacts, templates, and automations so multiple teams can share a single installation
while keeping data separate.

## Features
- Event-driven workflow engine for automating emails.
- Push notification delivery via drivers like Expo or OneSignal.
- JSON:API compliant endpoints for integrating with any client.
- Horizon-powered Redis queues and a scheduler for timed tasks.
- Demo seeder and health check to explore the API quickly.

## Requirements
- PHP 8.3+
- Composer
- Node.js & npm
- PostgreSQL
- Redis

## Installation
1. Clone the repository and install PHP dependencies:
   ```bash
   composer install
   ```
2. Copy the environment file and configure connection details:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Create a PostgreSQL database and update `.env` with your credentials.
4. Start Redis locally and ensure `REDIS_CLIENT=predis`.
5. Run database migrations:
   ```bash
   php artisan migrate
   ```

## Running Queues and Scheduler
Horizon manages Redis queues and provides a dashboard:
```bash
php artisan horizon
```

Run the scheduler to process scheduled tasks:
```bash
php artisan schedule:work
```

To run the scheduler via cron in production, add:
```cron
* * * * * cd /path/to/trigger-engage && php artisan schedule:run >> /dev/null 2>&1
```

## Usage
With queues and the scheduler running, send named events to the API to trigger automations.  
Each event belongs to a workspace and may include contact information. Automations listen for these
events and dispatch emails or other jobs through the queue.

## Push Notifications
To send pushes:
1. Configure push settings for your workspace (Expo or OneSignal) via the Filament admin panel.
2. Register device tokens for contacts:
   ```bash
   curl -X POST http://localhost/api/contacts/{contact_id}/device-tokens \
     -H "Authorization: Bearer <token>" \
     -H "X-Workspace: demo" \
     -H "Content-Type: application/json" \
     -d '{"token":"ExponentPushToken[xxx]","driver":"expo"}'
   ```
3. Add a **Send Push Notification** step to an automation with a templated title and body.
4. Ingest events as shown above to queue and deliver the notification.

## Demo Data
Seed a workspace with a contact, template, and automation:
```bash
php artisan make:demo
```
The command prints an API token and example requests:
```bash
curl -X POST http://localhost/api/events \
  -H "Authorization: Bearer <token>" \
  -H "X-Workspace: demo" \
  -H "Content-Type: application/json" \
  -d '{"name":"signup","contact_email":"contact@example.com"}'
```

## Testing
Run the test suite using Pest via Artisan:
```bash
php artisan test
```
The first test exercises the `/health` endpoint which returns `{ "ok": true }`.

## Health Check
After installing dependencies, you can verify the application is responding:
```bash
php artisan serve
# in another terminal
curl http://localhost:8000/health
```

## License
MIT
