<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhillipsAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function processHeartbeat(Request $request)
    {
        // \Log::info($request->all());
        $phillips_account = PhillipsAccount::query()->where('email', $request->email)->first();
        $phillips_account->last_email_update = Carbon::now();
        $phillips_account->push();

        return;
    }

    public function processInitResponse(Request $request)
    {
        // \Log::info($request->all());

        return;
    }
}
