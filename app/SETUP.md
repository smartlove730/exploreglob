# Blog Platform Setup Guide

Complete guide to set up and configure your scalable blogging platform with AI-powered content generation.

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and NPM (for frontend assets)
- Database (MySQL, PostgreSQL, or SQLite)
- Google Gemini API Key ([Get it here](https://aistudio.google.com/app/apikey))

## Installation Steps

### 1. Clone/Download the Project

```bash
cd blog-platform
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Environment Configuration

Create a `.env` file from `.env.example`:

```bash
cp .env.example .env
```

Or create `.env` manually with the following configuration:

```env
APP_NAME="Blog Platform"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite
# OR for MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=blog_platform
# DB_USERNAME=root
# DB_PASSWORD=

# Queue Configuration
QUEUE_CONNECTION=database

# Gemini AI Configuration
GEMINI_API_KEY=your-gemini-api-key-here
GEMINI_MODEL=gemini-2.0-flash-exp
GEMINI_REQUEST_TIMEOUT=180

# API Authentication (for cron jobs)
API_KEY=your-secure-random-api-key-here
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Setup Database

**For SQLite:**
```bash
touch database/database.sqlite
```

**For MySQL/PostgreSQL:**
Create the database manually, then update `.env` with credentials.

### 6. Run Migrations

```bash
php artisan migrate
```

### 7. Seed Database (Optional)

```bash
php artisan db:seed
```

This will populate countries, categories, and sample data.

### 8. Build Frontend Assets

```bash
npm run build
```

For development:
```bash
npm run dev
```

### 9. Start Development Server

```bash
php artisan serve
```

Visit: `http://localhost:8000`

## Queue Setup

Blogs are generated asynchronously using Laravel queues. You need to run a queue worker:

```bash
php artisan queue:work
```

### Production Queue Setup (Supervisor)

For production, use Supervisor to keep the queue worker running:

**Install Supervisor:**
```bash
sudo apt-get install supervisor  # Ubuntu/Debian
```

**Create config file** `/etc/supervisor/conf.d/blog-platform-worker.conf`:

```ini
[program:blog-platform-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-your-project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path-to-your-project/storage/logs/worker.log
stopwaitsecs=3600
```

**Start Supervisor:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start blog-platform-worker:*
```

## Scheduled Tasks (Cron Jobs)

Laravel's scheduler handles automated blog generation. Add this to your server's crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

The schedule is configured in `routes/console.php`. By default, it runs daily at 2 AM.

### Customize Schedule

Edit `routes/console.php` to change the schedule:

```php
// Every 6 hours
Schedule::command('blogs:generate-scheduled --limit=1')
    ->everySixHours()
    ->withoutOverlapping()
    ->runInBackground();

// Every hour
Schedule::command('blogs:generate-scheduled --limit=1')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
```

## API Configuration

### Generate API Key

Generate a secure random API key for authentication:

```bash
# Using OpenSSL
openssl rand -hex 32

# Using PHP
php -r "echo bin2hex(random_bytes(16));"
```

Add it to your `.env`:
```env
API_KEY=your-generated-api-key-here
```

### Test API

```bash
# Check status
curl http://localhost:8000/api/v1/generation-status

# Generate blog (requires API key)
curl -X POST http://localhost:8000/api/v1/generate-blog \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"country_id": 1, "category_id": 1}'
```

See `API_DOCUMENTATION.md` for complete API documentation.

## Manual Blog Generation

You can generate blogs manually using artisan commands:

```bash
# Generate for all countries and categories (1 blog each)
php artisan blogs:generate-scheduled

# Generate for specific country
php artisan blogs:generate-scheduled --country=1 --limit=2

# Generate for specific category
php artisan blogs:generate-scheduled --category=5 --limit=1

# Generate for specific country and category
php artisan blogs:generate-scheduled --country=1 --category=5 --limit=1
```

## External Cron Job Setup

Instead of Laravel scheduler, you can use external cron to call the API:

```bash
# Add to crontab (crontab -e)
0 2 * * * curl -X POST https://your-domain.com/api/v1/generate-blogs-for-country \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"country_id": 1, "limit": 1}'
```

## Troubleshooting

### Jobs Not Processing

1. **Check queue worker is running:**
   ```bash
   php artisan queue:work
   ```

2. **Verify queue connection:**
   Check `.env`: `QUEUE_CONNECTION=database`

3. **Check failed jobs:**
   ```bash
   php artisan queue:failed
   ```

4. **Retry failed jobs:**
   ```bash
   php artisan queue:retry all
   ```

### API Authentication Issues

1. **Verify API key is set:**
   ```bash
   php artisan tinker
   >>> config('app.api_key')
   ```

2. **Check middleware is registered:**
   Check `bootstrap/app.php` has the middleware alias.

### Blog Generation Failing

1. **Check Gemini API key:**
   ```bash
   php artisan tinker
   >>> config('gemini.api_key')
   ```

2. **Review logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Test API connection:**
   Check your Gemini API quota and limits.

### Scheduler Not Running

1. **Test scheduler:**
   ```bash
   php artisan schedule:test
   ```

2. **List scheduled tasks:**
   ```bash
   php artisan schedule:list
   ```

3. **Verify crontab:**
   ```bash
   crontab -l
   ```

## Production Deployment Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- [ ] Generate secure `APP_KEY`
- [ ] Set secure `API_KEY`
- [ ] Configure production database
- [ ] Set up queue worker (Supervisor)
- [ ] Configure cron job for scheduler
- [ ] Set up SSL certificate
- [ ] Configure web server (Nginx/Apache)
- [ ] Set up log rotation
- [ ] Configure backup strategy
- [ ] Monitor queue and job failures
- [ ] Set up monitoring/alerting

## Directory Structure

```
blog-platform/
├── app/
│   ├── Console/Commands/         # Artisan commands
│   ├── Http/
│   │   ├── Controllers/          # Web controllers
│   │   │   └── Api/              # API controllers
│   │   └── Middleware/           # Middleware
│   ├── Jobs/                     # Queue jobs
│   └── Models/                   # Eloquent models
├── config/                       # Configuration files
├── database/
│   ├── migrations/               # Database migrations
│   └── seeders/                  # Database seeders
├── routes/
│   ├── api.php                   # API routes
│   ├── web.php                   # Web routes
│   └── console.php               # Scheduled tasks
├── resources/views/              # Blade templates
└── storage/logs/                 # Log files
```

## Support

For issues or questions:
1. Check the logs: `storage/logs/laravel.log`
2. Review `API_DOCUMENTATION.md` for API usage
3. Check Laravel documentation: https://laravel.com/docs

## License

This project is open-sourced software.
