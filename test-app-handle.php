<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
$user->email_verified_at = null;
$user->save();

Auth::login($user);

$response = app()->handle(
    Illuminate\Http\Request::create('/email/verification-notification', 'POST')
);

echo "HTTP Status: " . $response->getStatusCode() . "\n";
echo "Session Data:\n";
print_r(session()->all());
