<?php

namespace App\Listeners;

use App\Models\Vehicle;
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
        $vehicle = Vehicle::query()->where('id', $event->bid->vehicle_id)->first();
        $vehicle->current_bid = $event->bid->amount;
        $vehicle->push();
        // \Log::info("Bid created not");
        // \Log::info($event -> bid->status);
    }
}
