<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Event;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;

Event::listen(MessageSending::class, function (MessageSending $event) {
    echo "Message is SENDING...\n";
    echo "To: " . implode(', ', array_keys($event->message->getTo() ?? [])) . "\n";
    echo "Subject: " . $event->message->getSubject() . "\n";
});

Event::listen(MessageSent::class, function (MessageSent $event) {
    echo "Message SENT successfully!\n";
});

try {
    $user = App\Models\User::first();
    $user->email_verified_at = null;
    $user->save();
    
    echo "User email: " . $user->email . "\n";
    $user->sendEmailVerificationNotification();
    echo "Notification dispatched.\n";
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
