<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'id',
        'auction_session_id',
        'phillips_account_id',
        'phillips_vehicle_id',
        'url',
        'current_bid',
        'start_amount',
        'maximum_amount',
        'lazy_stage_increment',
        'aggressive_stage_increment',
        'sniping_stage_increment',
        'status'
    ];

    public function auctionSession()
    {
        return $this->belongsTo(AuctionSession::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function phillipsAccount()
    {
        return $this->belongsTo(PhillipsAccount::class);
    }
}
