# Uptime Monitor API (Laravel)

Simple API for registering URLs, checking them on a schedule, storing check history, and sending email notifications when a site goes down or recovers.

## Tech

- Laravel (installed via `composer create-project` in this repo)
- PHP 8.4+
- DB: MySQL
- Queue: `database` driver (jobs table)
- Scheduler: Laravel scheduler running every minute

## Setup

This project includes a convenient setup script. Run this command to install dependencies, create your `.env` file, generate an application key, run migrations, and build frontend assets:

```bash
composer run setup
```

MySQL notes:
- Create a database named `uptime_monitor` (or update `DB_DATABASE` in `.env`).
- Update `DB_USERNAME` / `DB_PASSWORD` to match your local MySQL.

## Queue + Scheduler (required for checks + emails)

Run these in separate terminals:

```bash
php artisan queue:work
php artisan schedule:work
```

What runs:
- Scheduler triggers `monitors:dispatch-checks` every minute.
- That command dispatches `CheckMonitorJob` for monitors that are due (based on `check_interval` + `last_checked_at`).
- The job calls `App\Services\MonitorCheckService` to do the HTTP check, store history, update status, and queue email notifications.

## Configuration

Add to `.env`:

```env
UPTIME_ALERT_EMAIL=alerts@example.com
UPTIME_CHECK_TIMEOUT=10
```

Notes:
- If `UPTIME_ALERT_EMAIL` is empty, emails are skipped.
- Default mailer in `.env.example` is `log`, so emails will appear in `storage/logs/laravel.log` unless you configure SMTP.

## Assumptions & Decisions

- **MySQL**: matches typical production usage and what most interviewers expect; SQLite would also work but isn’t the default here.
- **Database queue driver**: easiest to run anywhere without Redis; good enough for an assessment.
- **`check_interval`**: interpreted as **minutes** between checks. A monitor is “due” when `last_checked_at <= now() - check_interval`.
- **Downtime `threshold`**: number of **consecutive failed checks** required before marking a monitor `down` and queuing the “down” email.

## API Endpoints

### Create monitor

`POST /api/monitors`

```json
{
  "url": "https://example.com",
  "check_interval": 5,
  "threshold": 3
}
```

- `url`: required, valid http/https URL, unique (duplicates return 422)
- `check_interval`: optional, default 5, min 1, max 60 (minutes)
- `threshold`: optional, default 3, min 1 (consecutive failures)

### List monitors

`GET /api/monitors`

Returns all monitors with:
`id, url, check_interval, threshold, status, last_checked_at, uptime_percentage, created_at`.

### Monitor history

`GET /api/monitors/{id}/history`

Query params:
- `page` (default 1)
- `per_page` (default 15, max 100)

If the monitor doesn’t exist:

```json
{ "message": "Monitor not found." }
```

## Monitoring Rules Implemented

- 2xx and 3xx responses are treated as UP.
- 4xx and 5xx responses are treated as DOWN.
- Connection/DNS/timeout failures are stored as DOWN with `status_code = 0` and `response_time_ms = null`.
- A monitor is marked DOWN only after reaching its configured consecutive-failure `threshold`.
- When a DOWN monitor becomes UP again, it’s marked UP and a recovery email is queued.
- Emails are queued (not sent inline) so checks stay fast.

## Design Notes

- Controllers are thin; monitoring logic lives in `App\Services\MonitorCheckService`.
- `uptime_percentage` is computed from history (so it’s `null` until at least one check exists).

## Run It Live (quick demo)

Create a monitor (PowerShell):

```powershell
$body = @{ url = 'https://example.com'; check_interval = 1; threshold = 2 } | ConvertTo-Json
Invoke-RestMethod -Method Post -Uri 'http://127.0.0.1:8000/api/monitors' -ContentType 'application/json' -Body $body
```

Force a check dispatch (without waiting for scheduler tick):

```bash
php artisan monitors:dispatch-checks
```

View history:

```powershell
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/monitors/1/history?per_page=15' | ConvertTo-Json -Depth 10
```

## Explain It (talk track)

- `routes/api.php` routes requests into `MonitorController` / `MonitorHistoryController`
- `StoreMonitorRequest` validates inputs + applies defaults
- `MonitorResource` / `MonitorCheckHistoryResource` shape responses
- migrations define `monitors` + `monitor_check_histories`
- `MonitorCheckService` performs the HTTP check and applies threshold rules (down/recovered + queued emails)
- scheduler in `bootstrap/app.php` runs `monitors:dispatch-checks` every minute, which dispatches `CheckMonitorJob`

## Version Requirements

This assessment prompt asks for Laravel 13.x on PHP 8.4+.

This repo currently installs on Laravel 12.x because the local environment available here is PHP 8.2.12 (Laravel 13 requires PHP 8.3+). The code is written to be compatible with Laravel 13, and upgrading is just a dependency bump once you’re on PHP 8.4+.

To upgrade dependencies after installing PHP 8.4+ locally:

```bash
composer require laravel/framework:^13.0 -W
composer update -W
php artisan test
```

## Tests

```bash
php artisan test
```

Includes:
- Endpoint tests for creating/listing monitors and the custom 404 message for history.
- Service test for threshold behavior and email queuing.
