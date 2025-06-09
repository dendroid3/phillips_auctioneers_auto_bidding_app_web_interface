<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::get('auction/{id}', function () {
    return Inertia::render('Auction');
})->middleware(['auth', 'verified'])->name('auction');

Route::get('scrape_auctions', function () {
    shell_exec('systemctl --user start scrapeAuctions.service');
    return response() -> json("Scrapping auctions in the background...");
})->name('auctions.scrape');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
