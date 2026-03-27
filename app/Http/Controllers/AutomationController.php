<?php

namespace App\Http\Controllers;

use App\Jobs\RunAutomationJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AutomationController extends Controller
{
    public function run(Request $request, ?int $automationConfigId = null): Response
    {
        $forceRun = $request->boolean('force', false);
        RunAutomationJob::dispatch($automationConfigId, $forceRun);

        return response($forceRun ? 'Automation started (forced).' : 'Automation started', 200);
    }
}
