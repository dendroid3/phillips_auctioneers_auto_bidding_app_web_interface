<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;

class SnipingController extends Controller
{
    public function init(Request $request)
    {
        // \Log::info($request->all());
        $vehicles = Vehicle::query()->where(
            [
                'auction_session_id' => 1, //$request->auction_account_id,
                // 'phillips_account_id' => $request->phillips_account_id,
                // 'status' => 'active'
            ]
        )->take(3)->get();

        // \Log::info($vehicles);

        return response()->json($vehicles);
    }
}
