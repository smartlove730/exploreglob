<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AutomationRule;
use App\Services\AutomationService;
use App\Models\DriveApiKey;
use App\Services\DriveService;

$rule = AutomationRule::find(1);
$svc = app(AutomationService::class);
$reflect = new ReflectionClass($svc);
$method = $reflect->getMethod('resolveMediaItems');
$method->setAccessible(true);
$media = $method->invoke($svc, $rule);
echo "Total Media Items: " . count($media) . "\n";

$payload = $rule->media_source_payload;
$driveApiKey = DriveApiKey::query()
    ->whereKey((int) ($payload['drive_api_key_id'] ?? 0))
    ->where('is_active', true)
    ->first();
$drivePayload = app(DriveService::class)->fetchMediaFromDriveLink((string) ($payload['drive_link'] ?? ''), $driveApiKey);
echo "Drive Payload Media count: " . count($drivePayload['media'] ?? []) . "\n";
