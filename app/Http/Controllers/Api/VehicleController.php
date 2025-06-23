<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Vehicle;
use App\Models\BidStage;
class VehicleController extends Controller
{
    public function create(Request $request)
    {
        $vehicle = new Vehicle();
        $vehicle->auction_session_id = $request->auction_id;
        $vehicle->phillips_vehicle_id = $request->id;
        $vehicle->save();
        return response()->json([
            "message" => "vehicle created successfully"
        ]);
    }

    public function update(Request $request)
    {
        $vehicle = Vehicle::query()->where('phillips_vehicle_id', $request['id'])->first();
        $vehicle->start_amount = $request->start_amount;
        $vehicle->maximum_amount = $request->maximum_amount;
        $vehicle->updated_at = Carbon::now();
        $vehicle->push();

        $lazy_stage = BidStage::query()->where('vehicle_id', $vehicle->id)->where('name', 'lazy')->first();
        $lazy_stage->start_time = $request->lazy_stage['start_time'];
        $lazy_stage->end_time = $request->lazy_stage['end_time'];
        $lazy_stage->increment = $request->lazy_stage['increment'];
        $lazy_stage->push();

        $aggressive_stage = BidStage::query()->where('vehicle_id', $vehicle->id)->where('name', 'aggressive')->first();
        $aggressive_stage->start_time = $request->aggressive_stage['start_time'];
        $aggressive_stage->end_time = $request->aggressive_stage['end_time'];
        $aggressive_stage->increment = $request->aggressive_stage['increment'];
        $aggressive_stage->push();


        $sniping_stage = BidStage::query()->where('vehicle_id', $vehicle->id)->where('name', 'sniping')->first();
        $sniping_stage->start_time = $request->sniping_stage['start_time'];
        $sniping_stage->end_time = $request->sniping_stage['end_time'];
        $sniping_stage->increment = $request->sniping_stage['increment'];
        $sniping_stage->push();

        \Log::info($vehicle);
        \Log::info($lazy_stage);
        \Log::info($aggressive_stage);
        \Log::info($sniping_stage);

        if (
            $vehicle->start_amount &&
            $vehicle->maximum_amount &&
            $lazy_stage->start_time &&
            $lazy_stage->end_time &&
            $lazy_stage->increment &&
            $aggressive_stage->start_time &&
            $aggressive_stage->end_time &&
            $aggressive_stage->increment &&
            $sniping_stage->start_time &&
            $sniping_stage->end_time &&
            $sniping_stage->increment
        ) {
            $vehicle->status = 'active';
            $vehicle->push();

            return response()->json([
                "message" => $vehicle->phillips_vehicle_id . " fully configured."
            ]);
        } else {
            return response()->json([
                "message" => $vehicle->phillips_vehicle_id . " NOT fully configured."
            ]);
        }

    }

    public function storeUrls(Request $request)
    {
        \Log::info("storeURLs Called");
        $last_vehicle_id = "";
        $last_vehicle_url = "file:///home/wanjohi/Downloads/bid_success.html";

        // For each object, find vehicle where phillips_account_id is like to the object -> id;
        foreach ($request->all() as $vehicleData) {
            \Log::info($vehicleData);
            $vehicle = Vehicle::query()
                // ->where('phillips_vehicle_id', $vehicleData->url)
                ->where('phillips_vehicle_id', 'LIKE', $vehicleData['id'] . '%')
                ->first();
            $vehicle->url = $vehicleData['url'];
            $vehicle->push();

            $last_vehicle_id = $vehicle->id;
            // $last_vehicle_url = $vehicle -> url;
            \Log::info($vehicle);
        }
        return response()->json([
            'last_vehicle_id' => $last_vehicle_id,
            'last_vehicle_url' => $last_vehicle_url
        ]);
    }
}
