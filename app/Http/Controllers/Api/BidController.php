<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\PhillipsAccount;
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
        // \Log::info($request->all());
        $bid = new Bid();
        $bid -> amount = $request -> amount;
        $bid -> status = $request -> status;
        $bid -> vehicle_id = $request -> vehicle_id;
        $bid -> phillips_account_id = PhillipsAccount::query() -> where('email', $request -> phillips_account_email)->first()->id;
        $bid -> bid_stage_id = 1;
        $bid -> save();

      
        return response() -> json($bid);
    }
}
