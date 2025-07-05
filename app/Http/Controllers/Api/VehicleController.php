<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\PlaceBid;
use App\Models\Bid;
use App\Models\PhillipsAccount;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Vehicle;
use App\Models\BidStage;
use App\Events\NotificationFromInitAuctionTestEvent;

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
        $vehicle->lazy_stage_increment = $request->lazy_stage_increment;
        $vehicle->aggressive_stage_increment = $request->aggressive_stage_increment;
        $vehicle->sniping_stage_increment = $request->sniping_stage_increment;
        $vehicle->updated_at = Carbon::now();
        $vehicle->push();

        if (
            $vehicle->start_amount &&
            $vehicle->maximum_amount &&
            $vehicle->lazy_stage_increment &&
            $vehicle->aggressive_stage_increment &&
            $vehicle->sniping_stage_increment
        ) {
            $auction = $vehicle->auctionSession;
            if ($auction->status == 'configured') {
                $auction->status = 'active';
                $auction->push();
            }
            $active_account = PhillipsAccount::query()->where('status', 'active')
                ->inRandomOrder()
                ->first();

            $vehicle->status = 'active';
            $vehicle->phillips_account_id = $active_account->id;
            $vehicle->push();

            if (BidStage::query()->where('auction_session_id', $auction->id)->where('status', 'active')->exists()) {
                $activeBidStage = BidStage::query()->where('auction_session_id', $auction->id)->where('status', 'active')->first();
                $activeBidStageName = $activeBidStage->name . "_stage_increment";
                $activeBidStageIncrement = $vehicle->$activeBidStageName;

                // Check if the vehicle already has a bid
                if (count($vehicle->bids) > 0) {
                    if ($vehicle->bids()->latest()->value('amount') == 1000) {
                        $amountToBid = $vehicle->start_amount;
                    } else {
                        $amountToBid = $vehicle->bids()->latest()->value('amount') + 1;
                    }
                } else {
                    $amountToBid = $vehicle->start_amount;
                }
                PlaceBid::dispatch(
                    url: $vehicle->url,
                    amount: $amountToBid,
                    maximum_amount: $vehicle->maximum_amount,
                    increment: $activeBidStageIncrement,
                    email: $active_account->email,
                    password: $active_account->account_password,
                    vehicle_id: $vehicle->id,
                    vehicle_name: $vehicle->phillips_vehicle_id,
                    bid_stage_name: $activeBidStage->name,
                    bid_stage_id: $activeBidStage->id
                )->onQueue('placeBids')
                    ->delay(now()->addSeconds(value: 2));
                ;
            }

            // Assign sniping account to vehicle
            $type = "success";
            $title = "SUCCESS: " . $vehicle -> phillips_vehicle_id ." FULLY CONFIGURED";
            $description = "Changes recorded successfully. " . $vehicle->phillips_vehicle_id . " fully configured. Sniping will be done by account " . $active_account->email . ". We will place a bid in a moment.";
        } else {
            $type = "fail";
            $title = "FAILURE: " . $vehicle -> phillips_vehicle_id . " NOT CONFIGURED";
            $description = "Changes recorded successfully. " . $vehicle->phillips_vehicle_id . " NOT fully configured.";
        }

        $id = \Str::random(10);

        NotificationFromInitAuctionTestEvent::dispatch($id, $type, $title, $description);
        return response()->json([
            "success" => true
        ]);

    }

    public function storeUrls(Request $request)
    {
        $last_vehicle_id = "";
        $last_vehicle_url = "";

        // For each object, find vehicle where phillips_account_id is like to the object -> id;
        foreach ($request->all() as $vehicleData) {
            $vehicle = Vehicle::query()
                ->where('phillips_vehicle_id', 'LIKE', $vehicleData['id'] . '%')
                ->first();
            $vehicle->url = $vehicleData['url'];
            $vehicle->push();

            $last_vehicle_id = $vehicle->id;
            $last_vehicle_url = "http://phillips.adilirealestate.com/bidSuccess.html";
        }
        return response()->json([
            'last_vehicle_id' => $last_vehicle_id,
            'last_vehicle_url' => $last_vehicle_url
        ]);
    }

    public function dropOff(Request $request)
    {
        $vehicle = Vehicle::query()->where('phillips_vehicle_id', $request->id)->first();
        $vehicle->status = 'dropped';
        $vehicle->push();

        return response()->json([
            "message" => $vehicle->phillips_vehicle_id . " successfully droped, no more bids will be placed for the vehicle."
        ]);
    }

    public function getbids(Request $request)
    {
        $vehicle = Vehicle::query()->where('phillips_vehicle_id', $request->vehicle_id)->first();
        $bids = Bid::with([
            'vehicle' => function ($query) {
                $query->select('id', 'phillips_vehicle_id');
            },
            'phillipsAccount' => function ($query) {
                $query->select('id', 'email');
            },
            'bidStage' => function ($query) {
                $query->select('id', 'name');
            }
        ])
            ->where("vehicle_id", $vehicle->id) // plural if array of IDs
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($bids);
    }
}
