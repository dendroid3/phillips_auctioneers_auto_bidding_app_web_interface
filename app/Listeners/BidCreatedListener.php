<?php

namespace App\Listeners;

use App\Models\Vehicle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\BidCreatedEvent;
use App\Models\PhillipsAccount;
use App\Jobs\PlaceBid;

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

        if ($event->bid->status == "Outbidded") {
            $active_account = PhillipsAccount::query()->where('status', 'active')
                ->inRandomOrder()
                ->first();
            PlaceBid::dispatch(
                url: "http://phillips.adilirealestate.com/bidSuccess.html",// $request->url,
                amount: $vehicle -> current_bid + $vehicle->lazy_stage_increment,
                maximum_amount: $vehicle->maximum_amount,
                increment: $vehicle->lazy_stage_increment,
                email: $active_account->email,
                password: $active_account->account_password,
                vehicle_id: $vehicle->id,
                vehicle_name: $vehicle->phillips_vehicle_id,
                bid_stage: "lazy stage"
            )->onQueue('placeBids');
        }
    }
}
