<?php

use Illuminate\Support\Facades\Schedule;
use Carbon\Carbon;
use App\Models\PhillipsAccount;
use App\Jobs\MonitorEmail;

Schedule::call(function () {
    // DB::table('recent_users')->delete();

    $activeAccounts = PhillipsAccount::where('email_status', 'active')->get();

    // Process each account
    foreach ($activeAccounts as $account) {
        // \Log($account->id);
        $lastUpdate = Carbon::parse($account->last_email_update);
        if($lastUpdate->diffInMinutes(Carbon::now()) > 3){
            // \Log::info("Restarting Email Monitor");
            MonitorEmail::dispatch(
                    email: $account['email'],
                    email_password: $account['email_app_password']
                )->onQueue('email');
        }
    }
})->everyFiveSeconds();
