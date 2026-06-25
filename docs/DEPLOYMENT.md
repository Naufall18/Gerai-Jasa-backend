# Gerai Jasa — Backend Deployment & Operations Runbook

Laravel REST API (PHP 8.3+). This document covers what the backend needs to run
correctly in production.

## 1. Requirements

- PHP **8.3+** with extensions: `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `ctype`, `json`, `gd` (intervention/image)
- **MySQL 8** (the canonical engine — `DB_CONNECTION=mysql`)
- **Redis** (optional but recommended for cache/queue at scale)
- Composer 2

## 2. Environment

Copy `.env.example` → `.env` and set, at minimum:

| Key | Notes |
|-----|-------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | **`false`** in production (never leak stack traces) |
| `APP_KEY` | `php artisan key:generate` |
| `DB_CONNECTION=mysql`, `DB_*` | database credentials |
| `CORS_ALLOWED_ORIGINS` | comma-separated frontend origins (no `*` — credentials are on) |
| `QUEUE_CONNECTION` | `database` (default) or `redis` |
| `CACHE_STORE` | `database` (default) or `redis` |
| `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION` | payments |
| `XENDIT_SECRET_KEY`, `XENDIT_WEBHOOK_TOKEN` | payments |
| `FCM_SERVER_KEY`, `FCM_PROJECT_ID` | push (see note below) |
| `FONNTE_TOKEN` | WhatsApp |

> **Push notifications:** the current `NotificationService` uses the legacy FCM
> endpoint. Migrating to **FCM HTTP v1** (service account + `google/auth`) is a
> pending task; push will no-op until configured.

## 3. Deploy steps

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate          # first deploy only
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan event:cache
```

On redeploy, re-run `migrate --force` and refresh the caches
(`config:cache`, `route:cache`).

## 4. Background processes (required)

These are **not** optional — features silently break without them.

| Process | Command | Purpose |
|---------|---------|---------|
| Queue worker | `php artisan queue:work --queue=notifications,default --tries=3` | Booking confirmation / reminder notifications |
| Scheduler | cron: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1` | H-1 reminders **and** daily slot generation |

Scheduled jobs (see `routes/console.php`):
- `reminders:generate --type=day` — daily 08:00
- `reminders:generate --type=hour` — hourly
- `slots:generate --days=60` — daily 02:00 (keeps a rolling booking window; **slots run out without this**)

Use Supervisor/systemd to keep `queue:work` alive.

## 5. Payment webhooks

Register these public callback URLs in each gateway dashboard:

- Midtrans: `POST https://<host>/api/v1/webhooks/midtrans` (HMAC-SHA512 signature verified)
- Xendit: `POST https://<host>/api/v1/webhooks/xendit` (`X-Callback-Token` verified)

The corresponding secrets (`MIDTRANS_SERVER_KEY`, `XENDIT_WEBHOOK_TOKEN`) **must**
be set or the webhook fails closed (HTTP 500 "Gateway not configured").

## 6. Health checks

| Endpoint | Type | Meaning |
|----------|------|---------|
| `GET /up` | liveness | process is up |
| `GET /api/v1/health/ready` | readiness | DB + cache reachable; returns `503` when degraded |

Point the load balancer/orchestrator readiness probe at `/api/v1/health/ready`.

## 7. Observability

- Every request gets an `X-Request-Id` (generated or propagated from the gateway),
  echoed on the response and attached to **all** log lines for that request.
- For aggregators, set `LOG_CHANNEL=structured` (JSON lines) or add `structured`
  to `LOG_STACK`.
- Error tracking (Sentry) is a pending task — add `sentry/sentry-laravel` + DSN.

## 8. Security checklist (pre-prod)

- [ ] `APP_DEBUG=false`
- [ ] `CORS_ALLOWED_ORIGINS` lists only real frontend origins
- [ ] All gateway secrets set (webhooks fail closed otherwise)
- [ ] HTTPS enforced at the edge
- [ ] `queue:work` and `schedule:run` running and monitored
- [ ] DB backups configured (FKs use `RESTRICT` to protect financial history; entities are soft-deleted, not hard-deleted)
