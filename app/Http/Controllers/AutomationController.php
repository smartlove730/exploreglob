<?php

namespace App\Http\Controllers;

use App\Services\AutomationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AutomationController extends Controller
{
    public function __construct(
        private readonly AutomationService $automationService,
    ) {
    }

    public function run(Request $request, int $userId, ?int $automationConfigId = null): Response
    {
        abort_unless(Auth::user()?->isAdmin() || Auth::id() === $userId, 403);

        $forceRun = $request->boolean('force', false);
        if ($forceRun && !Auth::user()?->isAdmin()) {
            abort(403);
        }

        $scheduledLogs = $this->automationService->scheduleAutomations($automationConfigId, $forceRun, $userId);

        return response(
            ($forceRun ? 'Automation scheduling started (forced).' : 'Automation scheduling started.')
            .' Jobs queued: '.$scheduledLogs->count(),
            200
        );
    }
}
