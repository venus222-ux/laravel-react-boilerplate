<?php

namespace App\Listeners;

use App\Events\Auth\PasswordResetRequested as AuthPasswordResetRequested;
use App\Events\PasswordResetRequested;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendResetPasswordNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(AuthPasswordResetRequested $event): void
    {
        $event->user->notify(
            new ResetPasswordNotification($event->token)
        );
    }
}
