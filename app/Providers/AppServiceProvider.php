<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Contact;
use App\Services\TrackingToken;
use App\Services\Push\PushManager;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TrackingToken::class, fn () => new TrackingToken(config('app.key')));
        $this->app->singleton(PushManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user) {
            $workspace = currentWorkspace();

            if ($workspace !== null && $user->account_id !== $workspace->account_id) {
                return false;
            }

            return null;
        });

        Event::listen(MessageSending::class, function (MessageSending $event): void {
            $to = $event->message->getTo();
            $address = $to[0] ?? null;
            $email = $address?->getAddress();

            if ($email === null) {
                return;
            }

            $contact = Contact::where('email', $email)->first();
            if ($contact === null) {
                return;
            }

            $token = app(TrackingToken::class)->sign(['contact_id' => $contact->id]);
            $url = url("/t/u/{$token}");

            $event->message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$url}>");
        });
    }
}
