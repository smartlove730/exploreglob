<?php

namespace App\Jobs;

use App\Services\AutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunAutomationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?int $automationConfigId = null,
        public bool $forceRun = false,
        public ?int $automationLogId = null,
    )
    {
    }

    public function handle(AutomationService $automationService): void
    {
        $automationService->runAllAutomations($this->automationConfigId, $this->forceRun, $this->automationLogId);
    }
}
