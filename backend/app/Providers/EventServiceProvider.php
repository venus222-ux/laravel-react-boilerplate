<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

// Events
use App\Events\UserRegistered;
use App\Events\UserLoggedIn;
use App\Events\PasswordResetRequested;

// Listeners
use App\Listeners\SendWelcomeEmail;
use App\Listeners\LogUserLogin;
use App\Listeners\SendResetPasswordNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserRegistered::class => [
            SendWelcomeEmail::class,
        ],

        UserLoggedIn::class => [
            LogUserLogin::class,
        ],

        PasswordResetRequested::class => [
            SendResetPasswordNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
