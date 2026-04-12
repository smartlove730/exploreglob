<?php

namespace App\Http\Controllers;

use App\Services\AutomationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AutomationController extends Controller
{
    public function __construct(
        private readonly AutomationService $automationService,
    ) {
    }

    public function run(Request $request, int $userId, ?int $automationConfigId = null): Response
    {
        $forceRun = $request->boolean('force', false);
        $scheduledLogs = $this->automationService->scheduleAutomations($automationConfigId, $forceRun, $userId);

        return response(
            ($forceRun ? 'Automation scheduling started (forced).' : 'Automation scheduling started.')
            .' Jobs queued: '.$scheduledLogs->count(),
            200
        );
    }
}
