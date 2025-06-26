<?php

use App\Http\Controllers\Api\AuctionSessionController;
use App\Http\Controllers\Api\BidController;
use App\Http\Controllers\Api\SnipingController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\MonitoringController;
use Illuminate\Support\Facades\Route;

Route::group(["prefix" => "auction"], function () {
    Route::get("get_all", [
        AuctionSessionController::class,
        "getAll"
    ])->name("auctions.get");

    Route::post("create", [
        AuctionSessionController::class,
        "create"
    ])->name("auction.create");

    Route::get("{id}", [
        AuctionSessionController::class,
        "get"
    ])->name("auction.get");

    Route::post("initialize", [
        AuctionSessionController::class,
        "initialize"
    ]);

    // This will be called by the email monitoring script
    Route::post("new_email", [
        AuctionSessionController::class,
        "processNewEmail"
    ]);

    // This will be called by the initAuctionSession script with the result of the test
    Route::post("init_test_results", [
        AuctionSessionController::class,
        "processInitTestResults"
    ]);

    Route::group(['prefix' => 'bid_stages'], function () {
        Route::post('update', [
            AuctionSessionController::class,
            "updateBidStages"
        ]);
    });
});

Route::group(["prefix" => "vehicle"], function () {
    Route::post("create", [
        VehicleController::class,
        "create"
    ])->name("vehicle.create");

    Route::put("update", [
        VehicleController::class,
        "update"
    ])->name('vehicle.update');

    Route::post("storeUrls", [
        VehicleController::class,
        "storeUrls"
    ])->name("vehicle.storeUrl");
});

Route::group(['prefix' => 'sniping'], function () {
    Route::post('init', [
        SnipingController::class,
        "init"
    ])->name('snipping.init');
});

Route::group(['prefix' => 'bid'], function () {
    Route::post('create', [
        BidController::class,
        "create"
    ])->name('bid.create');

    Route::get('get_all_for_auction/{id}', [
        BidController::class,
        "getAllForAuction"
    ])->name('getAllForAuction');
});

Route::group(['prefix' => 'email'], function () {
    Route::post('start', [MonitoringController::class, 'start']);
    Route::post('stop/{email}', [MonitoringController::class, 'stop']);
    Route::get('status', [MonitoringController::class, 'status']);
});

