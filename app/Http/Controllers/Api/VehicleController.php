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
        \Log::info($request -> all());
        \Log::info($request -> aggressive_stage_increment);
        $vehicle = Vehicle::query()->where('phillips_vehicle_id', $request['id'])->first();
        $vehicle->start_amount = $request->start_amount;
        $vehicle->maximum_amount = $request->maximum_amount;
        $vehicle->lazy_stage_increment = $request->lazy_stage_increment;
        $vehicle->aggressive_stage_increment = $request->aggressive_stage_increment;
        $vehicle->sniping_stage_increment = $request->sniping_stage_increment;
        $vehicle->updated_at = Carbon::now();
        $vehicle->push();


        \Log::info($vehicle);

        if (
            $vehicle->start_amount &&
            $vehicle->maximum_amount &&
            $vehicle->lazy_stage_increment &&
            $vehicle->aggressive_stage_increment &&
            $vehicle->sniping_stage_increment
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
