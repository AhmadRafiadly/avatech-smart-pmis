<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Console\Commands\CleanupTestArtifacts::class,
        \App\Console\Commands\CleanupUserRoster::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // Authenticated Smart-PMIS pages and the login form must never be
        // served from browser cache (otherwise Back-after-logout still shows
        // protected pages and the cached login form 419s on stale CSRF).
        $middleware->appendToGroup('web', \App\Http\Middleware\PreventBackHistory::class);
        $middleware->alias([
            'active.user' => \App\Http\Middleware\EnsureActiveUser::class,
            'ceo.pm' => \App\Http\Middleware\EnsureCeoPmAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
