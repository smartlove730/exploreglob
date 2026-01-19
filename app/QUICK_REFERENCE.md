# Quick Reference Guide

## Essential Commands

### Start Development
```bash
# Start web server
php artisan serve

# Start queue worker (required for blog generation)
php artisan queue:work

# Build frontend assets
npm run build
# OR for development
npm run dev
```

### Generate Blogs
```bash
# All categories and countries
php artisan blogs:generate-scheduled

# Specific country
php artisan blogs:generate-scheduled --country=1 --limit=2

# Specific category  
php artisan blogs:generate-scheduled --category=5 --limit=1
```

### Queue Management
```bash
# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### Scheduler
```bash
# List scheduled tasks
php artisan schedule:list

# Test scheduler
php artisan schedule:test

# Run scheduler manually
php artisan schedule:run
```

## API Quick Examples

### Generate Single Blog
```bash
curl -X POST http://localhost:8000/api/v1/generate-blog \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"country_id": 1, "category_id": 5}'
```

### Generate for Country
```bash
curl -X POST http://localhost:8000/api/v1/generate-blogs-for-country \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"country_id": 1, "limit": 3}'
```

### Check Status
```bash
curl http://localhost:8000/api/v1/generation-status
```

## Environment Variables Checklist

```env
# Required
GEMINI_API_KEY=your-key
API_KEY=your-key
QUEUE_CONNECTION=database

# Optional
GEMINI_MODEL=gemini-2.0-flash-exp
GEMINI_REQUEST_TIMEOUT=180
```

## Cron Setup

Add to crontab (`crontab -e`):
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## File Locations

- **API Routes**: `routes/api.php`
- **API Controllers**: `app/Http/Controllers/Api/`
- **Blog Generation Job**: `app/Jobs/GenerateBlogs.php`
- **Scheduled Command**: `app/Console/Commands/GenerateScheduledBlogs.php`
- **Scheduler Config**: `routes/console.php`
- **Views**: `resources/views/`
- **Logs**: `storage/logs/laravel.log`

## Troubleshooting Commands

```bash
# Check config values
php artisan tinker
>>> config('gemini.api_key')
>>> config('app.api_key')

# Clear cache
php artisan config:clear
php artisan cache:clear

# View logs
tail -f storage/logs/laravel.log

# Check database
php artisan migrate:status
```
