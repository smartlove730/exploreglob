<?php

namespace App\Jobs;

use App\Services\AutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunAutomationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?int $automationConfigId = null)
    {
    }

    public function handle(AutomationService $automationService): void
    {
        $automationService->runAllAutomations($this->automationConfigId);
    }
}
