<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\UserCreatedEvent;
use App\Models\PhillipsAccount;
class UserCreatedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserCreatedEvent $event): void
    {
        $phillips_account = new PhillipsAccount();
        $phillips_account->user_id = $event->user->id;
        $phillips_account->email = $event->user->email;
        $phillips_account -> save();
    }
}
