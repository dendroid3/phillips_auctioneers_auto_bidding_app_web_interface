<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\PhillipsAccount;
use App\Models\AuctionSession;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class BidController extends Controller
{
    /**
     * @param mixed $bid
     * [
     *      vehicle_id,
     *      bid_amount,
     *      phillips_account_email
     *      status
     * ]
     */
    public function create(Request $request)
    {
        $bid = new Bid();
        $bid->amount = $request->amount;
        $bid->status = $request->status;
        $bid->vehicle_id = $request->vehicle_id;
        $bid->phillips_account_id = PhillipsAccount::query()->where('email', $request->phillips_account_email)->first()->id;
        $bid->bid_stage_id = $request->bid_stage_id;
        $bid->save();
        return response()->json($bid);
    }

    public function getAllForAuction(Request $request)
    {
        $decodedTitle = urldecode($request->id);
        $normalizedTitle = trim(preg_replace('/\s+/', ' ', $decodedTitle));

        $words = explode(' ', $normalizedTitle);
        $firstThreeWords = implode(' ', array_slice($words, 0, 3));

        $auction = AuctionSession::query()->where('title', 'LIKE', $firstThreeWords . '%')->first();

        $vehicles = $auction->vehicles;

        $vehicle_ids = [];

        foreach ($vehicles as $vehicle) {
            array_push($vehicle_ids, $vehicle->id);
        }
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
            ->whereIn("vehicle_id", $vehicle_ids) 
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        return response()->json($bids);
        // return true;
    }
}
