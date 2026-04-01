# Deployment Guide (Production SaaS)

This project now includes customer app features, billing, scheduling, queue workers, and token refresh jobs.  
Use this checklist for production deployment.

## 1) Prerequisites

- PHP 8.2+
- Composer
- Node.js + npm (for assets)
- MySQL 8+ (or compatible)
- Redis 6/7 (queue + cache recommended)
- Process manager (Supervisor/systemd/K8s)

## 2) Environment configuration

1. Copy env template:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
2. Set production-safe values:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://your-domain`
3. Configure DB/Redis/queue:
   - `DB_CONNECTION=mysql` (+ host/user/pass/db)
   - `CACHE_STORE=redis`
   - `QUEUE_CONNECTION=redis`
4. Configure required SaaS integrations:
   - `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`
   - `FACEBOOK_APP_ID`, `FACEBOOK_APP_SECRET`, `FACEBOOK_REDIRECT_URI`
   - `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`
   - `GOOGLE_DRIVE_CLIENT_ID`, `GOOGLE_DRIVE_CLIENT_SECRET`, `GOOGLE_DRIVE_REDIRECT_URI`
   - `GOOGLE_DRIVE_API_KEY` (if using Drive API key flow)
   - `API_KEY` (for protected automation endpoints)
   - `GEMINI_API_KEY` (if using AI content generation)

## 3) Install and build

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4) Queue workers and scheduler

### Supervisor (recommended)

Use `deploy/supervisor/laravel-worker.conf` and adjust paths to your server layout.

After placing config:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start exploreglob-worker:*
sudo supervisorctl start exploreglob-scheduler
```

### Alternative cron scheduler

If not running scheduler daemon, add:
```cron
* * * * * cd /var/www/exploreglob && php artisan schedule:run --no-interaction >> /dev/null 2>&1
```

## 5) Webhooks and external callbacks

- Razorpay webhook endpoint: `POST /api/razorpay/webhook`
- Ensure HTTPS and correct webhook secret.
- Configure Facebook/Google OAuth redirect URLs to match routes in this app.

## 6) Docker (optional)

A starter `docker-compose.yml` is included with:
- app (php-nginx)
- queue worker
- scheduler loop
- mysql
- redis

Bring up stack:
```bash
docker compose up -d
```

## 7) Post-deploy checks

- `php artisan about`
- `php artisan schedule:list`
- `php artisan queue:failed`
- Verify `/up` health endpoint
- Confirm queue jobs process (`storage/logs/worker.log` if using provided supervisor config)
