<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AutomationRule;
use App\Services\AutomationService;
use App\Models\AutomationQueueItem;

$rule = AutomationRule::find(1);
$payload = $rule->media_source_payload ?: [];
print_r($payload);
echo "Total Queue items for this rule: " . AutomationQueueItem::where('automation_rule_id', $rule->id)->count() . "\n";
