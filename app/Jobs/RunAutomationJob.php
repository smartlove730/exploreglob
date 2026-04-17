<?php

namespace App\Jobs;

use App\Models\AutomationPostLog;
use App\Services\AutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RunAutomationJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public function __construct(
        public ?int $automationConfigId = null,
        public bool $forceRun = false,
        public ?int $automationLogId = null,
    )
    {
    }

    public function handle(AutomationService $automationService): void
    {
        try {
            if ($this->automationLogId) {
                $log = AutomationPostLog::query()->find($this->automationLogId);

                if (!$log || $log->completed_at || in_array($log->status, ['cancelled', 'success', 'failed', 'skipped'], true)) {
                    return;
                }
            }

            $automationService->runAllAutomations($this->automationConfigId, $this->forceRun, $this->automationLogId);
        } catch (Throwable $exception) {
            $this->markLogFailed($exception->getMessage());

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->markLogFailed($exception->getMessage());
    }

    private function markLogFailed(string $error): void
    {
        if (!$this->automationLogId) {
            return;
        }

        AutomationPostLog::query()
            ->whereKey($this->automationLogId)
            ->whereNull('completed_at')
            ->whereNotIn('status', ['success', 'failed', 'cancelled', 'skipped'])
            ->update([
                'status' => 'failed',
                'message' => 'Automation execution failed: '.mb_strimwidth($error, 0, 1000, '...'),
                'completed_at' => now(),
            ]);
    }
}
