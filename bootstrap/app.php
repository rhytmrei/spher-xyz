<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
//        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prependToGroup('auth:api', \App\Http\Middleware\CookieToken::class);
        $middleware->alias([
            'auth.required' => \App\Http\Middleware\EnsureTokenGiven::class,
        ]);
    })
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['auth:api']],
    )
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
