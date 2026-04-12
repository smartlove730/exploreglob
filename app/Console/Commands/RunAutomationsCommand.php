<?php

namespace App\Console\Commands;

use App\Services\AutomationService;
use Illuminate\Console\Command;

class RunAutomationsCommand extends Command
{
    protected $signature = 'automations:run
                            {userId : User ID to run automations for}
                            {automationConfigId? : Optional automation config ID}
                            {--force : Ignore schedule and daily limit checks}';

    protected $description = 'Queue automation jobs for a specific user without requiring login';

    public function handle(AutomationService $automationService): int
    {
        $userId = (int) $this->argument('userId');
        $automationConfigId = $this->argument('automationConfigId');
        $automationConfigId = $automationConfigId !== null ? (int) $automationConfigId : null;
        $forceRun = (bool) $this->option('force');

        $scheduledLogs = $automationService->scheduleAutomations($automationConfigId, $forceRun, $userId);

        $this->info(
            ($forceRun ? 'Automation scheduling started (forced).' : 'Automation scheduling started.')
            .' Jobs queued: '.$scheduledLogs->count()
        );

        return self::SUCCESS;
    }
}
