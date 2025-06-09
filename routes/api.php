<?php

use App\Http\Controllers\Api\AuctionSessionController;
use App\Http\Controllers\Api\VehicleController;
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
});

Route::group(["prefix"=> "vehicle"], function () {    
    Route::post("create", [
        VehicleController::class,
        "create"
    ])->name("vehicle.create");

    Route::put("update", [
        VehicleController::class,
        "update"
    ])->name('vehicle.update');
});
