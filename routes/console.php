<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\RunAutomationJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule blog generation
// This runs daily at 2 AM - you can customize the schedule
Schedule::command('blogs:generate-scheduled --limit=1')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground();

// Example: Run every 6 hours (uncomment if needed)
// Schedule::command('blogs:generate-scheduled --limit=1')
//     ->everySixHours()
//     ->withoutOverlapping()
//     ->runInBackground();


// Generate sitemap daily at 1:00 AM
Schedule::command('sitemap:generate')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->runInBackground();


// Refresh Facebook long-lived tokens daily
Schedule::command('facebook:refresh-tokens')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground();

// Automation runner. Per-config frequency is enforced in AutomationService (runs_per_day).
Schedule::job(new RunAutomationJob())
    ->everyThirtyMinutes()
    ->withoutOverlapping();
