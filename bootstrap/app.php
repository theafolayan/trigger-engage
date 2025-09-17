<?php

declare(strict_types=1);

use App\Filament\AdminPanelProvider;
use App\Filament\BusinessPanelProvider;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\WorkspaceResolve;
use Illuminate\Auth\Middleware\Authenticate;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth' => Authenticate::class,
            'workspace' => WorkspaceResolve::class,
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withProviders([
        AdminPanelProvider::class,
        BusinessPanelProvider::class,
        \App\Providers\AuthDebugServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
