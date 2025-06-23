<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\BidStage;
use App\Events\AuctionSessionCreatedEvent;
class AuctionSessionCreatedListener
{
    public $bidStages = [
        [
            'start_time' => '11:00:00',
            'end_time' => '12:45:00',
            'name' => 'lazy',
        ],
        [
            'start_time' => '12:45:01',
            'end_time' => '12:55:00',
            'name' => 'aggressive',
        ],
        [
            'start_time' => '12:55:01',
            'end_time' => '13:00:00',
            'name' => 'sniping',
        ]
    ];
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AuctionSessionCreatedEvent $event): void
    {
        foreach ($this->bidStages as $bidStage) {
            $bid_stage = new BidStage;
            $bid_stage->auction_session_id = $event->AuctionSession->id;
            $bid_stage->start_time = $bidStage['start_time'];
            $bid_stage->end_time = $bidStage['end_time'];
            $bid_stage->name = $bidStage['name'];
            $bid_stage->save();
        }
    }
}
