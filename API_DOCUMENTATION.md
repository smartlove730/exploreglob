# Blog Platform API Documentation

This document describes the API endpoints available for automated blog generation via cron jobs or external services.

## Base URL

```
http://your-domain.com/api/v1
```

## Authentication

All blog generation endpoints require API key authentication. Include your API key in one of the following ways:

1. **Header** (Recommended):
   ```
   X-API-Key: your-api-key-here
   ```

2. **Query Parameter**:
   ```
   ?api_key=your-api-key-here
   ```

## Environment Configuration

Add these to your `.env` file:

```env
# Gemini AI API Configuration
GEMINI_API_KEY=your-gemini-api-key-here
GEMINI_MODEL=gemini-2.0-flash-exp
GEMINI_REQUEST_TIMEOUT=180

# API Authentication
API_KEY=your-secure-api-key-here

# Queue Configuration
QUEUE_CONNECTION=database
```

## Endpoints

### 1. Generate Single Blog

Generate a blog post for a specific category and country.

**Endpoint:** `POST /api/v1/generate-blog`

**Headers:**
```
X-API-Key: your-api-key
Content-Type: application/json
```

**Request Body:**
```json
{
  "country_id": 1,
  "category_id": 5
}
```

**Response (202 Accepted):**
```json
{
  "success": true,
  "message": "Blog generation job queued successfully",
  "data": {
    "country_id": 1,
    "category_id": 5,
    "category_name": "Technology",
    "status": "queued"
  }
}
```

**Error Response (422 Validation Error):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "country_id": ["The country id field is required."],
    "category_id": ["The category id field is required."]
  }
}
```

---

### 2. Generate Blogs for Country

Generate multiple blogs for all categories in a specific country.

**Endpoint:** `POST /api/v1/generate-blogs-for-country`

**Headers:**
```
X-API-Key: your-api-key
Content-Type: application/json
```

**Request Body:**
```json
{
  "country_id": 1,
  "limit": 3
}
```

- `country_id` (required): The ID of the country
- `limit` (optional): Number of blogs to generate per category (default: 1, max: 10)

**Response (202 Accepted):**
```json
{
  "success": true,
  "message": "Successfully queued 15 blog generation job(s)",
  "data": {
    "country_id": 1,
    "categories_count": 5,
    "jobs_per_category": 3,
    "total_jobs_dispatched": 15
  }
}
```

---

### 3. Generate Blogs for Category

Generate blogs for a specific category across all countries.

**Endpoint:** `POST /api/v1/generate-blogs-for-category`

**Headers:**
```
X-API-Key: your-api-key
Content-Type: application/json
```

**Request Body:**
```json
{
  "category_id": 5,
  "limit": 2
}
```

- `category_id` (required): The ID of the category
- `limit` (optional): Number of blogs to generate per country (default: 1, max: 10)

**Response (202 Accepted):**
```json
{
  "success": true,
  "message": "Successfully queued 20 blog generation job(s)",
  "data": {
    "category_id": 5,
    "category_name": "Technology",
    "jobs_per_country": 2,
    "total_jobs_dispatched": 20
  }
}
```

---

### 4. Get Generation Status

Check if the API is operational.

**Endpoint:** `GET /api/v1/generation-status`

**No Authentication Required**

**Response (200 OK):**
```json
{
  "success": true,
  "message": "API is operational",
  "data": {
    "status": "active",
    "timestamp": "2024-01-15 10:30:45",
    "queue_connection": "database"
  }
}
```

---

### 5. Get Categories (Public)

Get list of all categories.

**Endpoint:** `GET /api/v1/categories`

**Query Parameters:**
- `country_id` (optional): Filter by country ID

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Technology",
      "slug": "technology",
      "description": "Latest tech news and trends",
      "country_id": 1,
      "status": 1,
      "country": {
        "id": 1,
        "name": "United States",
        "code": "US"
      }
    }
  ]
}
```

---

## Cron Job Setup

### Option 1: Using Laravel Scheduler (Recommended)

Add this to your server's crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler is already configured in `routes/console.php` to run blog generation daily at 2 AM.

You can customize the schedule in `routes/console.php`:

```php
// Run daily at 2 AM
Schedule::command('blogs:generate-scheduled --limit=1')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground();

// Or run every 6 hours
Schedule::command('blogs:generate-scheduled --limit=1')
    ->everySixHours()
    ->withoutOverlapping()
    ->runInBackground();
```

### Option 2: External Cron Job

Set up a cron job on your server to hit the API:

```bash
# Run every day at 2 AM
0 2 * * * curl -X POST https://your-domain.com/api/v1/generate-blogs-for-country \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"country_id": 1, "limit": 1}'
```

### Option 3: Manual Artisan Command

Run manually using Laravel's artisan command:

```bash
# Generate blogs for all countries and categories
php artisan blogs:generate-scheduled

# Generate for specific country
php artisan blogs:generate-scheduled --country=1 --limit=2

# Generate for specific category
php artisan blogs:generate-scheduled --category=5 --limit=1

# Generate for specific country and category
php artisan blogs:generate-scheduled --country=1 --category=5 --limit=1
```

## Queue Processing

Blogs are generated asynchronously using Laravel queues. Make sure to run the queue worker:

```bash
php artisan queue:work
```

For production, use a process manager like Supervisor to keep the queue worker running:

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

## Error Handling

Jobs will automatically retry up to 3 times if they fail. Retry intervals:
- 1st retry: After 1 minute
- 2nd retry: After 5 minutes
- 3rd retry: After 15 minutes

Failed jobs will be logged in `storage/logs/laravel.log`.

## Rate Limiting

Consider implementing rate limiting based on your Gemini API quota:

1. Add rate limiting middleware to API routes
2. Configure maximum blogs per day/hour
3. Monitor API usage in logs

## Testing

Test the API using cURL:

```bash
# Test status endpoint
curl https://your-domain.com/api/v1/generation-status

# Test blog generation
curl -X POST https://your-domain.com/api/v1/generate-blog \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"country_id": 1, "category_id": 5}'
```

Or use tools like Postman or Insomnia for easier testing.

## Troubleshooting

### Jobs not processing
- Ensure queue worker is running: `php artisan queue:work`
- Check queue connection in `.env`: `QUEUE_CONNECTION=database`
- Verify database connection

### API authentication failing
- Check `API_KEY` is set in `.env`
- Verify API key is sent correctly in headers
- Check middleware is registered in `bootstrap/app.php`

### Blog generation failing
- Verify `GEMINI_API_KEY` is set correctly
- Check Gemini API quota/limits
- Review logs in `storage/logs/laravel.log`
- Ensure model name is correct in config

### Scheduled tasks not running
- Verify crontab is configured correctly
- Check Laravel scheduler is running: `php artisan schedule:list`
- Review scheduler output: `php artisan schedule:test`
