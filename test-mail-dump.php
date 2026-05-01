<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::first();
$notification = new App\Notifications\VerifyEmailQueued();
$mailMessage = $notification->toMail($u);

echo "Subject: " . $mailMessage->subject . "\n";
echo "Action Text: " . $mailMessage->actionText . "\n";
echo "Action URL: " . $mailMessage->actionUrl . "\n";
print_r($mailMessage->introLines);
print_r($mailMessage->outroLines);
