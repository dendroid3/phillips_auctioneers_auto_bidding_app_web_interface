<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\BidStage;

class AuctionSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $bidStages = BidStage::query()->where('auction_session_id', $this->id)->get();

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
        return parent::toArray($request);
    }
}
