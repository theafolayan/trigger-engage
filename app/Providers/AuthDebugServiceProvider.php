<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AuthDebugServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(Attempting::class, function (Attempting $event): void {
            Log::info('Auth attempt', [
                'guard' => $event->guard,
                'credentials' => Arr::except($event->credentials, ['password']),
            ]);
        });

        Event::listen(Failed::class, function (Failed $event): void {
            Log::warning('Auth failed', [
                'guard' => $event->guard,
                'user_id' => $event->user?->id,
                'credentials' => Arr::except($event->credentials, ['password']),
            ]);
        });

        Event::listen(Login::class, function (Login $event): void {
            Log::info('Auth success', [
                'guard' => $event->guard,
                'user_id' => $event->user->id,
                'is_admin' => $event->user->is_admin,
            ]);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            Log::info('Auth logout', [
                'guard' => $event->guard,
                'user_id' => $event->user?->id,
            ]);
        });
    }
}
