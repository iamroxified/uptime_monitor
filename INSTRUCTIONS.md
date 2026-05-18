You are helping me implement a Laravel backend assessment project. Build it in a clean, realistic, senior-developer style, not over-engineered, and make sure I can understand and explain every decision.

Project: Uptime Monitor API

Goal:
Create a Laravel 13.x API using PHP 8.4+ and a relational database inside the uptime_monitor folder. The app should allow users to register URLs for monitoring, check them periodically, store check history, determine whether sites are up/down, and send email notifications when a site goes down or recovers.

I have also created a git repo called uptime_monitor where each stage will be pushed to

Required API Endpoints:

1. POST /api/monitors
Request body:
{
  "url": "https://example.com",
  "check_interval": 5,
  "threshold": 3
}

Rules:
- url is required, valid, unique, and must be HTTP/HTTPS.
- check_interval is optional, default 5, min 1, max 60.
- threshold is optional, default 3, min 1.
- Initial status should be "pending".
- Duplicate URLs must return 422.

Success response:
{
  "data": {
    "id": 1,
    "url": "https://example.com",
    "check_interval": 5,
    "threshold": 3,
    "status": "pending",
    "last_checked_at": null,
    "uptime_percentage": null,
    "created_at": "2026-05-13T10:00:00.000000Z"
  }
}

2. GET /api/monitors
Return all monitors with:
- id
- url
- check_interval
- threshold
- status
- last_checked_at
- uptime_percentage
- created_at

3. GET /api/monitors/{id}/history
Return paginated history ordered by checked_at descending.

Query params:
- page, default 1
- per_page, default 15, max 100

Response:
{
  "data": [
    {
      "id": 1,
      "monitor_id": 1,
      "status_code": 200,
      "response_time_ms": 245,
      "is_up": true,
      "checked_at": "2026-05-13T10:05:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 50
  }
}

If monitor is not found, return:
{
  "message": "Monitor not found."
}

Important monitoring rules:
- 2xx and 3xx responses count as UP.
- 4xx, 5xx, timeout, DNS failure, or connection failure count as DOWN.
- For timeout/connection failure, save status_code as 0 and response_time_ms as null.
- Only mark a monitor as DOWN after reaching its consecutive failure threshold.
- If a previously DOWN monitor becomes UP again, update status to UP and send recovery email.
- Send email when a site first goes DOWN and when it recovers.

Architecture requirements:
- Use migrations, models, controllers, API resources, form requests, jobs, services, and mail classes.
- Keep controllers thin.
- Put monitoring/checking logic inside a service class.
- Use Laravel Scheduler and Queue Jobs for periodic checks.
- Add proper validation and error handling.
- Include tests for the main endpoints and monitoring logic where possible.
- Include a clear README with setup instructions, queue/scheduler instructions, assumptions, API examples, and design decisions.

Suggested database tables:

monitors:
- id
- url unique
- check_interval integer default 5
- threshold integer default 3
- status enum/string: pending, up, down
- last_checked_at nullable timestamp
- created_at
- updated_at

monitor_check_histories:
- id
- monitor_id foreign key
- status_code integer
- response_time_ms nullable integer
- is_up boolean
- checked_at timestamp
- created_at
- updated_at

Implementation style:
- Write clean, readable Laravel code.
- Avoid unnecessary complexity.
- Use meaningful naming.
- Add comments only where they clarify business logic.
- Do not generate fake exaggerated features outside the assessment requirement.
- After generating each major file, briefly explain what it does and why it exists.
- Also provide recommended Git commit messages after each major step.

To make it feel like your own real project, do this:
- After generating the first version, review the code and simplify anything that looks too perfect or over-engineered. Use my own naming style, add a practical README, test it locally, and commit in small realistic steps.

Suggested commit flow:

-Initial Laravel setup
-Add monitor and history migrations
-Implement monitor API endpoints
-Add API resources and validation
-Implement uptime check service
-Add queue job and scheduler
-Add email notifications
-Add tests and README
-Final cleanup before submission