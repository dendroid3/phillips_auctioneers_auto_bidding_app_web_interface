<?php

namespace App\Http\Resources;

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

        // Transform bid stages
        foreach ($this->bidStages as $stage) {
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
            'start_amount' => $this->start_amount,
            'maximum_amount' => $this->maximum_amount,
            'current_bid' => optional($this->bids->sortByDesc('created_at')->first())->amount,
            'last_bid_time' => optional($this->bids->sortByDesc('created_at')->first())->created_at,
            'status' => $this->status,
            'lazy_stage' => $stages['lazy_stage'],
            'aggressive_stage' => $stages['aggressive_stage'],
            'sniping_stage' => $stages['sniping_stage']
        ];
    }
}
