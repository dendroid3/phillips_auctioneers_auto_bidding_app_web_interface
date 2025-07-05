<?php

namespace App\Http\Resources;

use App\Models\BidStage;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray($request)
    {
        // Initialize stage objects
        $stages = [
            'lazy_stage' => null,
            'aggressive_stage' => null,
            'sniping_stage' => null
        ];

        $bidStages = BidStage::query()->where('vehicle_id', $this->id)->get();

        foreach ($bidStages as $stage) {
            $key = strtolower($stage->name) . '_stage';
            if (array_key_exists($key, $stages)) {
                $stages[$key] = [
                    'id' => $stage->id,
                    'start_time' => $stage->start_time,
                    'end_time' => $stage->end_time,
                    'increment' => $stage->increment,
                    'status' => $stage->status
                ];
            }
        }
        return [
            'id' => $this->phillips_vehicle_id,
            'start_amount' => $this->start_amount ? $this->start_amount : 0,
            'maximum_amount' => $this->maximum_amount ? $this->maximum_amount : 0,
            'lazy_stage_increment' => $this->lazy_stage_increment ? $this->lazy_stage_increment : 0,
            'aggressive_stage_increment' => $this->aggressive_stage_increment ? $this->aggressive_stage_increment : 0,
            'sniping_stage_increment' => $this->sniping_stage_increment ? $this->sniping_stage_increment : 0,
            'current_bid' => optional($this->bids->sortByDesc('created_at')->first())->amount,
            'current_bid_status' => optional($this->bids->sortByDesc('created_at')->first())->status,
            'last_bid_time' => optional($this->bids->sortByDesc('created_at')->first())->created_at,
            'status' => $this->status,
            'bids' => $this->bids
        ];
    }
}
