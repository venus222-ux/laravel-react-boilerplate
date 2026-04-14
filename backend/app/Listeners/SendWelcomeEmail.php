<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        // Example using Mail (you can replace with Notification if preferred)

        Mail::raw('Welcome to our platform!', function ($message) use ($event) {
            $message->to($event->user->email)
                    ->subject('Welcome!');
        });

        // OR better (recommended):
        // $event->user->notify(new WelcomeNotification());
    }
}
