<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\VehicleCreatedEvent;
use App\Models\BidStage;
class VehicleCreatedListener
{
    public $bidStages = [
        [
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'name' => 'lazy',
            'increment' => 5000
        ],
        [
            'start_time' => '12:00:01',
            'end_time' => '12:55:00',
            'name' => 'aggressive',
            'increment' => 8000
        ],
        [
            'start_time' => '12:55:01',
            'end_time' => '13:00:00',
            'name' => 'sniping',
            'increment' => 10000
        ]
    ];
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
    public function handle(VehicleCreatedEvent $event): void
    {
        foreach ($this->bidStages as $bidStage) {
            $bid_stage = new BidStage;
            $bid_stage->vehicle_id = $event->vehicle->id;
            $bid_stage->start_time = $bidStage['start_time'];
            $bid_stage->end_time = $bidStage['end_time'];
            $bid_stage->name = $bidStage['name'];
            $bid_stage->increment = $bidStage['increment'];
            $bid_stage->save();
        }
    }
}
