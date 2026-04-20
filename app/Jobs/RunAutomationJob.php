<?php

namespace App\Jobs;

use App\Models\AutomationPostLog;
use App\Services\AutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RunAutomationJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $timeout = 900;
    public bool $failOnTimeout = true;
    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function uniqueId(): string
    {
        return 'automation:'.$this->automationConfigId.':'.$this->automationLogId.':'.($this->forceRun ? '1' : '0');
    }

    public int $uniqueFor = 1200;

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
            $automationService->markStaleInProgressLogsAsFailed();

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
