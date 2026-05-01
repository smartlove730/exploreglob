<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::first();
$u->email_verified_at = null;
$u->save();

$request = Illuminate\Http\Request::create('/email/verification-notification', 'POST');
$request->setUserResolver(function() use ($u) { return $u; });

$controller = new App\Http\Controllers\Auth\EmailVerificationNotificationController();
$response = $controller->store($request);

echo "Status: " . $response->getStatusCode() . "\n";
$logs = App\Models\EmailLog::orderBy('id', 'desc')->take(2)->get();
foreach($logs as $log) {
    echo "Log: {$log->type} - {$log->status}\n";
}
