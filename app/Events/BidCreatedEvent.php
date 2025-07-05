<?php

namespace App\Events;

use App\Models\Vehicle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Bid;
use App\Models\PhillipsAccount;
use App\Models\BidStage;
class BidCreatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bid;
    public function __construct(Bid $bid)
    {
        $this->bid = $bid;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('public-channel'),
        ];
    }

    /**
     * Customize the broadcast payload.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        $amount = $this->bid->amount;

        $formatter = new \NumberFormatter('en_KE', \NumberFormatter::CURRENCY);
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 0);
        $formatted_amount = $formatter->formatCurrency($amount, 'KES');

        $phillips_account_email = PhillipsAccount::find($this->bid->phillips_account_id)->email;
        $title = '';
        $type = '';
        $description = '';
        $vehicle = Vehicle::find($this->bid->vehicle_id);
        $vehicle_name = $vehicle->phillips_vehicle_id;

        $vehicle->current_bid = $this->bid->amount;
        $vehicle->push();

        $auction = $vehicle->auctionSession;
        $activeBidStage = BidStage::query()->where('auction_session_id', $auction->id)->where('status', 'active')->first();
        $activeBidStageName = $activeBidStage->name . "_stage_increment";
        $activeBidStageIncrement = $vehicle->$activeBidStageName;


        if ($this->bid->status == 'Highest') {
            $type = "success";
            $title = "SUCCESS: " . $vehicle_name;
            $description = "Bid of " . $formatted_amount . " placed successfully on " . $vehicle_name . " with account " . $phillips_account_email . ". We are the highest.";
        } else if ($this->bid->status == "Outbidded") {
            $type = "amber";
            $title = "OUTBIDDED: " . $vehicle_name;

            if (($vehicle->current_bid + $activeBidStageIncrement < $vehicle->maximum_amount)) {
                if ($activeBidStage->name == 'lazy') {
                    $description = "Bid of " .
                        $formatted_amount . " placed successfully on " .
                        $vehicle_name . " with account " . $phillips_account_email .
                        ". However, there is a higher bid. Bidding again will go not beyond the vehicle's budget, therefore, we will bid again, starting at " .
                        $vehicle->current_bid + $activeBidStageIncrement .
                        " after " .
                        env('LAZY_STAGE_BID_DELAY_IN_MINUTES') .
                        " minutes.";
                } else if ($activeBidStage->name == 'aggressive') {
                    $description = "Bid of " .
                        $formatted_amount . " placed successfully on " .
                        $vehicle_name . " with account " . $phillips_account_email .
                        ". However, there is a higher bid. Bidding again will go not beyond the vehicle's budget, therefore, we will bid again, starting at " .
                        $vehicle->current_bid + $activeBidStageIncrement .
                        " immediately.";
                } else if ($activeBidStage->name == 'sniping') {
                    $description = "Bid of " .
                        $formatted_amount . " placed successfully on " .
                        $vehicle_name . " with account " . $phillips_account_email .
                        ". However, there is a higher bid. Bidding again will go not beyond the vehicle's budget, therefore, we will bid again, starting at " .
                        $vehicle->current_bid + $activeBidStageIncrement .
                        " immediately.";
                }
            } else if (
                ($vehicle->current_bid + $activeBidStageIncrement > $vehicle->maximum_amount) &&
                ($vehicle->current_bid !== $vehicle->maximum_amount)
            ) {
                if ($activeBidStage->name == 'lazy') {
                    $description = "Bid of " .
                        $formatted_amount . " placed successfully on " .
                        $vehicle_name . " with account " . $phillips_account_email .
                        ". However, there is a higher bid. If we place a bid of " .
                        $vehicle->current_bid + (int) $activeBidStageIncrement .
                        " (competitor's: " .
                        $vehicle->current_bid .
                        " + " .
                        $activeBidStage->name .
                        " stage increment: " .
                        $activeBidStageIncrement .
                        ") " .
                        " then we will go beyond the vehicle's maximum amount of " .
                        $vehicle->maximum_amount .
                        ". Therefore, we will try one last time with the maximum amount after " .
                        env('LAZY_STAGE_BID_DELAY_IN_MINUTES') .
                        " minutes.";
                } else if ($activeBidStage->name == 'aggressive') {
                    $description = "Bid of " .
                        $formatted_amount . " placed successfully on " .
                        $vehicle_name . " with account " . $phillips_account_email .
                        ". However, there is a higher bid. If we place a bid of " .
                        $vehicle->current_bid + (int) $activeBidStageIncrement .
                        " (competitor's: " .
                        $vehicle->current_bid .
                        " + " .
                        $activeBidStage->name .
                        " stage increment: " .
                        $activeBidStageIncrement .
                        ") " .
                        " then we will go beyond the vehicle's maximum amount of " .
                        $vehicle->maximum_amount .
                        ". Therefore, we will try one last time with the maximum amount immediately.";
                } else if ($activeBidStage->name == 'sniping') {
                    $description = "Bid of " .
                        $formatted_amount . " placed successfully on " .
                        $vehicle_name . " with account " . $phillips_account_email .
                        ". However, there is a higher bid. If we place a bid of " .
                        $vehicle->current_bid + (int) $activeBidStageIncrement .
                        " (competitor's: " .
                        $vehicle->current_bid .
                        " + " .
                        $activeBidStage->name .
                        " stage increment: " .
                        $activeBidStageIncrement .
                        ") " .
                        " then we will go beyond the vehicle's maximum amount of " .
                        $vehicle->maximum_amount .
                        ". Therefore, we will try one last time with the maximum amount immediately.";
                }
            }
        } else if ($this->bid->status == "Outbudgeted") {
            $type = "fail";
            $title = "OUTBUDGETTED: " . $vehicle_name;
            $description = "Bid of " .
                $formatted_amount .
                " placed successfully on " .
                $vehicle_name .
                " with account " .
                $phillips_account_email .
                ". However, there is a higher bid but we're out of budget. No more bids will be placed. The vehicle is now classified as outbudgeted. You can increase the maximum amount and save to start bidding again.";
        } else if ($this->bid->status == "Toppled") {
            $type = "amber";
            $title = "TOPPLED: " . $vehicle_name;
            $description = "Bid of " . $formatted_amount . " on " . $vehicle_name . " has been placed by competitor. The bid has been successfully logged.";
            if ($activeBidStage->name == 'sniping') {
                $description = "Bid of " . $formatted_amount . " on " . $vehicle_name . " has been placed by competitor. The bid has been successfully logged. We are in the sniping bid stage, now we wait for the trigger time to place new bids and chase the highest. Fingers crossed.";
            }
        } else if ($this->bid->status == "Highest (Test)" || $this->bid->status == "Outbidded (Test)") {
            $type = "amber";
            $title = "TEST BID SUCCESS";
            $description = "Bid of " . $formatted_amount . " placed successfully on " . $vehicle_name . " with account " . $phillips_account_email . ". We've confirmed that the account is authorized to participate in this auction.";
        }

        return [
            'id' => $this->bid->id,
            'type' => $type,
            'title' => $title . " [" . number_format($this->bid->amount, 0) . "]",
            'description' => $description,
            'time' => \Carbon\Carbon::now()->format('H:i:s')
        ];
    }

    public function broadcastAs(): string
    {
        return 'bid.created';
    }
}
