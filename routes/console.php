<?php

use App\Jobs\PlaceBid;
use App\Models\AuctionSession;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Schedule;
use Carbon\Carbon;
use App\Models\PhillipsAccount;
use App\Jobs\MonitorEmail;
use App\Events\NotificationFromInitAuctionTestEvent;

use Symfony\Component\Process\Process;

function isProcessRunning($email, $password, $interval)
{
    $pattern = "python3 /home/wanjohi/Code/web/phillips/email/index.py $email $password $interval";
    $escapedPattern = escapeshellarg($pattern);

    $cmd = "pgrep -f $escapedPattern";
    exec($cmd, $output);

    return count($output) > 1;
}

// Check if the email monitor is still making heartbeats, if not restart the job
Schedule::call(function () {
    $activeAccounts = PhillipsAccount::where('email_status', 'active')->get();

    // Process each account
    foreach ($activeAccounts as $account) {
        $lastUpdate = Carbon::parse($account->last_email_update);
        if ($lastUpdate->diffInMinutes(Carbon::now()) > 3) {
            MonitorEmail::dispatch(
                email: $account['email'],
                email_app_password: $account['email_app_password'],
                interval: 15
            )->onQueue('email');
        }
    }
})->everyFiveSeconds();

// Monitor Auction Times, change Auction and BidStage statuses
Schedule::call(function () {
    $now = now();
    $today = $now->toDateString();
    $currentTime = $now->toTimeString();

    $activeAuction = AuctionSession::query()
        ->where('date', $today)
        ->whereTime('start_time', '<=', $currentTime)
        ->whereTime('end_time', '>=', $currentTime)
        ->first();


    if ($activeAuction) {
        if (Carbon::now()->format('H:i:s') > $activeAuction->start_time && Carbon::now()->format('H:i:s') < $activeAuction->end_time) {
            if ($activeAuction->status == 'configured') {
                $activeAuction->status = "active";
                $activeAuction->push();
            }

            // // Change Active Stage
            $bidStages = $activeAuction->bidStages;

            foreach ($bidStages as $bidStage) {
                if (Carbon::now()->format('H:i:s') > $bidStage->start_time && Carbon::now()->format('H:i:s') < $bidStage->end_time) {
                    if ($bidStage->name == 'lazy') {
                        if ($bidStage->status !== 'active') {
                            $bidStage->status = "active";
                            $bidStage->push();

                            $id = Str::random(10);
                            $type = 'amber';
                            $title = 'Lazy Bid Stage Intialized';
                            $description = "We have started the lazy stage now. We will place new bids on all active vehicles in the amount that is their respective current bid to ensure that we're still the highest";
                            NotificationFromInitAuctionTestEvent::dispatch($id, $type, $title, $description);
                        }

                        $active_account = PhillipsAccount::query()->where('status', 'active')
                            ->inRandomOrder()
                            ->first();

                        $activeVehicles = Vehicle::query()->where('status', 'active')->get();
                        foreach ($activeVehicles as $vehicle) {

                            $vehicle_bid_status = $vehicle->bids()->latest()->status;

                            if (count($vehicle->bids) > 0) {
                                if ($vehicle_bid_status !== 'Highest' || $vehicle_bid_status !== 'highest') {
                                    $activeBidStageName = $bidStage->name . "_stage_increment";
                                    $activeBidStageIncrement = $vehicle->$activeBidStageName;
                                    PlaceBid::dispatch(
                                        url: $vehicle->url,
                                        amount: $vehicle->maximum_amount,
                                        maximum_amount: $vehicle->current_bid,
                                        increment: (int) $activeBidStageIncrement,
                                        email: $active_account->email,
                                        password: $active_account->account_password,
                                        vehicle_id: $vehicle->id,
                                        vehicle_name: $vehicle->phillips_vehicle_id,
                                        bid_stage_name: $bidStage->name,
                                        bid_stage_id: $bidStage->id
                                    )->onQueue('placeBids');
                                }
                            } else {
                                $activeBidStageName = $bidStage->name . "_stage_increment";
                                $activeBidStageIncrement = $vehicle->$activeBidStageName;
                                PlaceBid::dispatch(
                                    url: $vehicle->url,
                                    amount: $vehicle->maximum_amount,
                                    maximum_amount: $vehicle->start_amount,
                                    increment: (int) $activeBidStageIncrement,
                                    email: $active_account->email,
                                    password: $active_account->account_password,
                                    vehicle_id: $vehicle->id,
                                    vehicle_name: $vehicle->phillips_vehicle_id,
                                    bid_stage_name: $bidStage->name,
                                    bid_stage_id: $bidStage->id
                                )->onQueue('placeBids');
                            }

                        }
                    } else if ($bidStage->name == 'aggressive') {
                        if ($bidStage->status !== 'active') {
                            $bidStage->status = "active";
                            $bidStage->push();

                            $id = Str::random(10);
                            $type = 'amber';
                            $title = 'Aggressive Bid Stage Intialized';
                            $description = "We have moved to the aggressive stage now. We will place new bids on all active vehicles in the amount that is their respective current bid to ensure that we're still the highest";
                            NotificationFromInitAuctionTestEvent::dispatch($id, $type, $title, $description);
                        }
                        $active_account = PhillipsAccount::query()->where('status', 'active')
                            ->inRandomOrder()
                            ->first();

                        $activeVehicles = Vehicle::query()->where('status', 'active')->get();
                        foreach ($activeVehicles as $vehicle) {
                            if (count($vehicle->bids) > 0) {
                                $vehicle_bid_status = $vehicle->bids()->latest()->status;

                                if ($vehicle_bid_status !== 'Highest' || $vehicle_bid_status !== 'highest') {
                                    $activeBidStageName = $bidStage->name . "_stage_increment";
                                    $activeBidStageIncrement = $vehicle->$activeBidStageName;

                                    PlaceBid::dispatch(
                                        url: $vehicle->url,
                                        amount: $vehicle->maximum_amount,
                                        maximum_amount: $vehicle->current_bid,
                                        increment: (int) $activeBidStageIncrement,
                                        email: $active_account->email,
                                        password: $active_account->account_password,
                                        vehicle_id: $vehicle->id,
                                        vehicle_name: $vehicle->phillips_vehicle_id,
                                        bid_stage_name: $bidStage->name,
                                        bid_stage_id: $bidStage->id
                                    )->onQueue('placeBids');
                                }
                            }
                        }
                    } else if ($bidStage->name == 'sniping') {
                        if ($bidStage->status !== 'active') {
                            $bidStage->status = "active";
                            $bidStage->push();

                            $id = Str::random(10);
                            $type = 'amber';
                            $title = 'Sniping Bid Stage Intialized';
                            $description = "We have moved to the sniping stage now. We will monitor the emails more aggressively (5 seconds), open tabs for each of the active vehicles, then wait for the last 2 minutes to place the bids.";
                            NotificationFromInitAuctionTestEvent::dispatch($id, $type, $title, $description);
                        }

                        $active_accounts = PhillipsAccount::query()->where('status', 'active')
                            ->get();

                        // Change all active vehicles to sniping
                        $vehicles = $activeAuction->vehicles;
                        foreach ($vehicles as $vehicle) {
                            // if (count($vehicle->bids) > 0) {
                            //     $vehicle_bid_status = $vehicle->bids()->latest()->status;

                            //     if ($vehicle_bid_status !== 'Highest' || $vehicle_bid_status !== 'highest') {
                            //         PlaceBid::dispatch(
                            //             url: $vehicle->url,
                            //             amount: $vehicle->maximum_amount,
                            //             maximum_amount: $vehicle->current_bid,
                            //             increment: (int) $activeBidStageIncrement,
                            //             email: $active_account->email,
                            //             password: $active_account->account_password,
                            //             vehicle_id: $vehicle->id,
                            //             vehicle_name: $vehicle->phillips_vehicle_id,
                            //             bid_stage_name: $bidStage->name,
                            //             bid_stage_id: $bidStage->id
                            //         )->onQueue('placeBids');
                            //     }
                            // }
                            if (
                                $vehicle->start_amount &&
                                $vehicle->maximum_amount &&
                                $vehicle->lazy_stage_increment &&
                                $vehicle->aggressive_stage_increment &&
                                $vehicle->sniping_stage_increment &&
                                $vehicle->status !== 'Outbudgeted' &&
                                $vehicle->status !== 'outbudgeted' &&
                                $vehicle->status !== 'dropped'
                            ) {
                                $vehicle->status = 'sniping';
                                $vehicle->push();
                            }
                        }

                        // Monitor emails more aggressively
                        foreach ($active_accounts as $account) {
                            $email = $account['email'];
                            $password = $account['email_app_password'];
                            $interval = 5;

                            if (isProcessRunning($email, $password, $interval)) {
                                continue;
                            }

                            // Start the process
                            $command = [
                                'python3',
                                '/home/wanjohi/Code/web/phillips/email/index.py',
                                $email,
                                $password,
                                $interval
                            ];

                            $process = new Process($command);
                            $process->setTimeout(3600);
                            $process->setIdleTimeout(null);
                            $process->start(function ($type, $buffer) use ($email) {
                                echo $type === Process::OUT ? "[OUT][$email] $buffer" : "[ERR][$email] $buffer";
                            });

                            // call sniping script
                        }


                    }
                } else {
                    $bidStage->status = "dormant";
                    $bidStage->push();
                }
            }
        } else {
            $bidStages = $activeAuction->bidStages;

            foreach ($bidStages as $bidStage) {
                $bidStage->status = "dormant";
                $bidStage->push();
            }

            $activeAuction->status = "elapsed";
            $activeAuction->push();
        }
    }
})->everyFiveSeconds();
