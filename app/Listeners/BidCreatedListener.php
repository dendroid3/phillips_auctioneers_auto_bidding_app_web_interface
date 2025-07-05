<?php

namespace App\Listeners;

use App\Models\BidStage;
use App\Models\Vehicle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\BidCreatedEvent;
use App\Models\PhillipsAccount;
use App\Jobs\PlaceBid;

use Illuminate\Support\Str;
use App\Events\NotificationFromInitAuctionTestEvent;

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
            if ($active_account) {
                $auction = $vehicle->auctionSession;
                $activeBidStage = BidStage::query()->where('auction_session_id', $auction->id)->where('status', 'active')->first();
                // \Log::info("bid id " . $event->bid->id);
                $activeBidStageName = $activeBidStage->name . "_stage_increment";
                $activeBidStageIncrement = $vehicle->$activeBidStageName;
                if (
                    ($activeBidStage->name == 'lazy') &&
                    ($vehicle->current_bid + $activeBidStageIncrement < $vehicle->maximum_amount)
                ) {
                    PlaceBid::dispatch(
                        url: $vehicle->url,
                        amount: $vehicle->current_bid + (int) $activeBidStageIncrement,
                        maximum_amount: $vehicle->maximum_amount,
                        increment: (int) $activeBidStageIncrement,
                        email: $active_account->email,
                        password: $active_account->account_password,
                        vehicle_id: $vehicle->id,
                        vehicle_name: $vehicle->phillips_vehicle_id,
                        bid_stage_name: $activeBidStage->name,
                        bid_stage_id: $activeBidStage->id
                    )->onQueue('placeBids')
                        ->delay(now()->addMinutes(value: (int) env('LAZY_STAGE_BID_DELAY_IN_MINUTES', 2)));

                } else if (
                    ($activeBidStage->name == 'lazy') &&
                    ($vehicle->current_bid + $activeBidStageIncrement > $vehicle->maximum_amount) &&
                    ($vehicle->current_bid !== $vehicle->maximum_amount)
                ) {
                    PlaceBid::dispatch(
                        url: $vehicle->url,
                        amount: $vehicle->maximum_amount,
                        maximum_amount: $vehicle->maximum_amount,
                        increment: (int) $activeBidStageIncrement,
                        email: $active_account->email,
                        password: $active_account->account_password,
                        vehicle_id: $vehicle->id,
                        vehicle_name: $vehicle->phillips_vehicle_id,
                        bid_stage_name: $activeBidStage->name,
                        bid_stage_id: $activeBidStage->id
                    )->onQueue('placeBids')
                        ->delay(now()->addMinutes(value: (int) env('LAZY_STAGE_BID_DELAY_IN_MINUTES', 2)));

                } else if (($vehicle->current_bid >= $vehicle->maximum_amount)) {
                    $vehicle->status = "outbudgeted";
                    $vehicle->push();
                    $id = Str::random(10);
                    $type = 'fail';
                    $title = "OUTBUDGETED: " . $vehicle->phillips_vehicle_id;
                    $description = $vehicle->phillips_vehicle_id . " has been out outbudgeted. We placed a bid of " .
                        $vehicle->current_bid .
                        " but was not enough to get use to the highest position. We will no longer bid on this vehicle.  The vehicle is now classified as outbudgeted. You can increase the maximum amount and save to start bidding again.";
                    NotificationFromInitAuctionTestEvent::dispatch($id, $type, $title, $description);
                }
                // \Log::info("bid id " . $event->bid->id . " ended!!");
            }
        }
    }
}
