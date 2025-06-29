<?php

use App\Models\AuctionSession;
use Illuminate\Support\Facades\Schedule;
use Carbon\Carbon;
use App\Models\PhillipsAccount;
use App\Jobs\MonitorEmail;

// Check if the email monitor is still making heartbeats, if not restart the job
Schedule::call(function () {
    $activeAccounts = PhillipsAccount::where('email_status', 'active')->get();

    // Process each account
    foreach ($activeAccounts as $account) {
        // \Log($account->id);
        $lastUpdate = Carbon::parse($account->last_email_update);
        if ($lastUpdate->diffInMinutes(Carbon::now()) > 3) {
            // \Log::info("Restarting Email Monitor");
            MonitorEmail::dispatch(
                email: $account['email'],
                email_password: $account['email_app_password']
            )->onQueue('email');
        }
    }
})->everyFiveSeconds();

// Monitor Auction Times, change Auction and BidStage statuses
Schedule::call(function () {
    $activeAuction = AuctionSession::query()->where('status', 'active')->first();

    if (Carbon::now() > "13:00:50") {
        $activeAuction->status = "elapsed";
        $activeAuction->push();

        // Change Active Stage
        $bidStages = $activeAuction->bidStages;

        foreach ($bidStages as $bidStage) {
            if ($activeAuction->status == 'active') {
                if (($bidStage->start_time < Carbon::now()) && ($bidStage->end_time > Carbon::now())) {
                    $bidStage->status = "active";
                    $bidStage->push();
                }
            } else {
                $bidStages->status="elapsed";
                $bidStages->push();
            }
        }
    }
})->everyMinute();
