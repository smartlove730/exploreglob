<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AutomationRule;
use App\Services\AutomationService;

$rules = AutomationRule::where('status', AutomationRule::STATUS_ACTIVE)->get();
echo "Active rules count: " . $rules->count() . "\n";

$svc = app(AutomationService::class);
foreach($rules as $rule) {
    echo "Rule ID: {$rule->id}, Name: {$rule->name}\n";
    echo "Next run at: {$rule->next_run_at}\n";
    $res = $svc->queueRule($rule, true); // force=true
    print_r($res);
}
