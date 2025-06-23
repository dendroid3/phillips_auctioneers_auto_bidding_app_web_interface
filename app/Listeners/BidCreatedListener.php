<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\BidCreatedEvent;

class BidCreatedListener
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
    public function handle(BidCreatedEvent $event): void
    {
        \Log::info("Bid created not");
        \Log::info($event -> bid->status);
    }
}
