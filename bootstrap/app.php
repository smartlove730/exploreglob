<?php

putenv('TMPDIR=' . __DIR__ . '/../sys_tmp');
ini_set('sys_temp_dir', __DIR__ . '/../sys_tmp');

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.key' => \App\Http\Middleware\ApiKeyMiddleware::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'subscription.active' => \App\Http\Middleware\EnsureActiveSubscription::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            '/whatsapp/message/recieved',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

$app->useStoragePath(__DIR__.'/../storage_new');
$app->useBootstrapPath(__DIR__.'/../bootstrap_new');

return $app;
