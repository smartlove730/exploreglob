<?php

namespace App\Jobs;

use App\Models\AutomationPostLog;
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
        if ($this->automationLogId) {
            $log = AutomationPostLog::query()->find($this->automationLogId);

            if (!$log || $log->completed_at || in_array($log->status, ['cancelled', 'success', 'failed', 'skipped'], true)) {
                return;
            }
        }

        $automationService->runAllAutomations($this->automationConfigId, $this->forceRun, $this->automationLogId);
    }
}
