<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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
