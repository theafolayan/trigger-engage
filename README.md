# Trigger Engage

Trigger Engage is an open-source, self-hosted, event-driven email automation tool built on Laravel.

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
