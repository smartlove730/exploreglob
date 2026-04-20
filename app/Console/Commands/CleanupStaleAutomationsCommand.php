<?php

namespace App\Console\Commands;

use App\Services\AutomationService;
use Illuminate\Console\Command;

class CleanupStaleAutomationsCommand extends Command
{
    protected $signature = 'automations:cleanup-stale';

    protected $description = 'Mark stale in-progress automation logs as failed';

    public function handle(AutomationService $automationService): int
    {
        $count = $automationService->markStaleInProgressLogsAsFailed();

        $this->info("Marked {$count} stale automation log(s) as failed.");

        return self::SUCCESS;
    }
}
