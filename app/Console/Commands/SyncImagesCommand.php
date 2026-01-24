<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncCategoryImagesJob;

class SyncImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'sync:images';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Dispatch the job to fetch and save category images from Pexels';

    /**
     * Execute the console command.
     */
   /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dispatching SyncCategoryImagesJob...');
        
        // This pushes the job to the queue
        \App\Jobs\SyncCategoryImagesJob::dispatch();

        // Use 'info' for green text or 'line' for standard text
        $this->info('Job successfully dispatched to the queue!');
        
        $this->comment('Reminder: Run "php artisan queue:work" to start downloading the images.');
    }
}