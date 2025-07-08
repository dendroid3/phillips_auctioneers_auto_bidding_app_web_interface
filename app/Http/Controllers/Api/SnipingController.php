<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;

class SnipingController extends Controller
{
    public function init(Request $request)
    {
        \Log::info("line 13");
        \Log::info($request->all());
        $vehicles = Vehicle::query()->where(
            [
                'auction_session_id' => (int) $request->auction_session_id,
                'phillips_account_id' => (int) $request->phillips_account_id,
                'status' => 'sniping'
            ]
        )->get();
        \Log::info($vehicles);
        \Log::info("line 23");
// $vehicles = Vehicle::query()->where(
//             [
//                 'auction_session_id' => 1,
//                 'phillips_account_id' => 1,
//                 'status' => 'sniping'
//             ]
//         )->get();
        foreach ($vehicles as $vehicle) {
            // $vehicle->url = "http://phillips.adilirealestate.com/try.html";
        }

        return response()->json($vehicles);
    }

    public function trigger(Request $request)
    {
        $vehicles = Vehicle::query()->where(
            [
                'auction_session_id' => (int) $request->auction_session_id,
                'phillips_account_id' => (int) $request->phillips_account_id,
                'status' => 'sniping'
            ]
        )->get();

        foreach ($vehicles as $vehicle) {
            $vehicle->url = "http://phillips.adilirealestate.com/bidSuccess.html";
        }

        return response()->json($vehicles);
    }
}
