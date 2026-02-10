# Scalable Blog Platform with AI Content Generation

A modern, scalable blogging platform built with Laravel that automatically generates blog posts using Google Gemini AI. Perfect for content automation, SEO-focused blogs, and multi-category content management.

## 🚀 Features

- **AI-Powered Content Generation**: Automatically generate high-quality blog posts using Google Gemini AI
- **Multi-Category Support**: Organize blogs by categories and countries
- **RESTful API**: Complete API for automated blog generation via cron jobs
- **Queue-Based Processing**: Asynchronous blog generation using Laravel queues
- **Scheduled Tasks**: Built-in Laravel scheduler for automated content generation
- **SEO Optimized**: Built-in SEO metadata and schema support
- **Responsive Design**: Modern, mobile-friendly UI with Bootstrap 5
- **Multi-Country Support**: Content personalization by country

## 📋 Requirements

- PHP 8.2+
- Composer
- Node.js & NPM
- Database (SQLite, MySQL, or PostgreSQL)
- Google Gemini API Key ([Get one here](https://aistudio.google.com/app/apikey))

## 🛠️ Quick Start

1. **Clone and Install:**
   ```bash
   composer install
   npm install
   ```

2. **Configure Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Setup Database:**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Start Development:**
   ```bash
   # Terminal 1: Web server
   php artisan serve
   
   # Terminal 2: Queue worker
   php artisan queue:work
   
   # Terminal 3: Frontend (optional)
   npm run dev
   ```

5. **Visit:** `http://localhost:8000`

## 📖 Documentation

- **[Setup Guide](SETUP.md)** - Complete installation and configuration instructions
- **[API Documentation](API_DOCUMENTATION.md)** - API endpoints for cron jobs and automation

## 🔌 API Endpoints

The platform provides RESTful API endpoints for automated blog generation:

- `POST /api/v1/generate-blog` - Generate a single blog
- `POST /api/v1/generate-blogs-for-country` - Generate blogs for all categories in a country
- `POST /api/v1/generate-blogs-for-category` - Generate blogs for a category across all countries
- `GET /api/v1/generation-status` - Check API status
- `GET /api/v1/categories` - List all categories

All endpoints require API key authentication. See [API_DOCUMENTATION.md](API_DOCUMENTATION.md) for details.

## 🤖 Automated Blog Generation

### Using Laravel Scheduler (Recommended)

The platform includes a built-in scheduler that runs daily at 2 AM:

```php
// Already configured in routes/console.php
Schedule::command('blogs:generate-scheduled --limit=1')
    ->dailyAt('02:00');
```

Set up cron to run the scheduler:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Using External Cron

Call the API directly from your server's crontab:

```bash
0 2 * * * curl -X POST https://your-domain.com/api/v1/generate-blogs-for-country \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"country_id": 1, "limit": 1}'
```

### Using Artisan Commands

Generate blogs manually:

```bash
# Generate sitemap.xml immediately
php artisan sitemap:generate
```

Sitemap generation is also scheduled daily at 1 AM in `routes/console.php`.


```bash
# All countries and categories
php artisan blogs:generate-scheduled

# Specific country
php artisan blogs:generate-scheduled --country=1 --limit=2

# Specific category
php artisan blogs:generate-scheduled --category=5
```

## ⚙️ Configuration

### Environment Variables

Required in `.env`:

```env
# Gemini AI
GEMINI_API_KEY=your-api-key-here
GEMINI_MODEL=gemini-2.0-flash-exp

# API Authentication
API_KEY=your-secure-api-key-here

# Queue
QUEUE_CONNECTION=database
```

### Queue Processing

Blogs are generated asynchronously. Run the queue worker:

```bash
php artisan queue:work
```

For production, use Supervisor (see [SETUP.md](SETUP.md)).

## 📁 Project Structure

```
blog-platform/
├── app/
│   ├── Console/Commands/      # Artisan commands (scheduled blog generation)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/           # API controllers for cron jobs
│   │   └── Middleware/        # API authentication
│   ├── Jobs/                  # Queue jobs (GenerateBlogs)
│   └── Models/                # Eloquent models
├── routes/
│   ├── api.php                # API routes
│   ├── web.php                # Web routes
│   └── console.php            # Scheduled tasks
├── config/
│   └── gemini.php             # Gemini AI configuration
└── resources/views/           # Blade templates
```

## 🔒 Security

- API key authentication for all generation endpoints
- Environment-based configuration (no hardcoded keys)
- SQL injection protection (Eloquent ORM)
- XSS protection (Blade templating)
- CSRF protection (Laravel middleware)

## 🎯 Use Cases

- **Content Marketing**: Automate blog content creation
- **SEO Blogs**: Generate SEO-optimized articles
- **Multi-language Sites**: Support multiple countries/categories
- **News Aggregation**: Automated content generation
- **Affiliate Marketing**: Content for product reviews

## 🚦 Monitoring & Logs

- View logs: `storage/logs/laravel.log`
- Queue status: `php artisan queue:failed`
- Scheduled tasks: `php artisan schedule:list`
- Test scheduler: `php artisan schedule:test`

## 📝 License

This project is open-sourced software licensed under the MIT license.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📞 Support

- **Documentation**: See [SETUP.md](SETUP.md) and [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- **Issues**: Check logs in `storage/logs/laravel.log`
- **Laravel Docs**: https://laravel.com/docs

## 🙏 Credits

- Built with [Laravel](https://laravel.com)
- AI powered by [Google Gemini](https://gemini.google.com)
- UI with [Bootstrap](https://getbootstrap.com)

---

**Made with ❤️ for automated content generation**
