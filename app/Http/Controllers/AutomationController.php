<?php

namespace App\Http\Controllers;

use App\Jobs\RunAutomationJob;
use Illuminate\Http\Response;

class AutomationController extends Controller
{
    public function run(?int $automationConfigId = null): Response
    {
        RunAutomationJob::dispatch($automationConfigId);

        return response('Automation started', 200);
    }
}
