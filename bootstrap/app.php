<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\ApplyRuntimePhpLimits::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\ApplyRuntimePhpLimits::class,
        ]);

        $middleware->alias([
            'api.key' => \App\Http\Middleware\ApiKeyMiddleware::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'subscription.active' => \App\Http\Middleware\EnsureActiveSubscription::class,
            'php.limits' => \App\Http\Middleware\ApplyRuntimePhpLimits::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
