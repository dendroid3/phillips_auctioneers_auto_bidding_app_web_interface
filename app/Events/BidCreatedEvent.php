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
        $amount = $this -> bid -> amount;

        $formatter = new \NumberFormatter('en_KE', \NumberFormatter::CURRENCY);
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 0);
        $formatted_amount = $formatter->formatCurrency($amount, 'KES');

        $phillips_account_email = PhillipsAccount::find($this->bid->phillips_account_id)->email;
        $title = '';
        $type = $this->bid->status == 'Highest' ? 'success' : 'fail';
        $description = '';
        $vehicle_name = Vehicle::find($this->bid->vehicle_id)->phillips_vehicle_id;

        if ($this->bid->status == 'Highest') {
            $title = "Bid Successful";
            $description = "Bid of " . $formatted_amount . " placed successfully on " . $vehicle_name . " with account " . $phillips_account_email . ". We are the highest.";
        } else if ($this->bid->status == "Outbidded") {
            $title = "Outbidded";
            $description = "Bid of " . $formatted_amount . " placed successfully on " . $vehicle_name . " with account " . $phillips_account_email . ". However, there is a higher bid. We will bid again.";
        } else if ($this->bid->status == "Outbudgeted") {
            $title = "Out of budget";
            $description = "Bid of " . $formatted_amount . " placed successfully on " . $vehicle_name . " with account " . $phillips_account_email . ". However, there is a higher bid but we're out of budget. No more bids will be placed.";
        }

        return [
            'id' => $this->bid->id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
        ];
    }

    public function broadcastAs(): string
    {
        return 'bid.created';
    }
}
