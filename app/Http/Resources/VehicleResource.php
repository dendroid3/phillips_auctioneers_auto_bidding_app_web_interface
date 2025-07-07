<?php

namespace App\Http\Resources;

use App\Models\BidStage;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Bid;
class VehicleResource extends JsonResource
{
    public function toArray($request)
    {
        // Get the current highest bid (most recent, and if same time, largest ID last)
        $currentBid = Bid::where('vehicle_id', $this->id)
            ->orderByDesc('created_at')
            ->orderBy('id') // Ascending order (smaller IDs first)
            ->first();

        return [
            'id' => $this->phillips_vehicle_id,
            'start_amount' => $this->start_amount ? $this->start_amount : 0,
            'maximum_amount' => $this->maximum_amount ? $this->maximum_amount : 0,
            'lazy_stage_increment' => $this->lazy_stage_increment ? $this->lazy_stage_increment : 0,
            'aggressive_stage_increment' => $this->aggressive_stage_increment ? $this->aggressive_stage_increment : 0,
            'sniping_stage_increment' => $this->sniping_stage_increment ? $this->sniping_stage_increment : 0,
            'current_bid' => optional($currentBid)->amount,
            'current_bid_status' => optional($this->bids->sortByDesc('created_at')->sortByDesc('id')->first())->status,
            'last_bid_time' => optional($this->bids->sortByDesc('created_at')->first())->created_at,
            'status' => $this->status,
            'bids' => $this->bids
        ];
    }
}
