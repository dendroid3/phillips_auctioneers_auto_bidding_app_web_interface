<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'id',
        'url',
        'auction_session_id',
        'phillips_vehicle_id',
        'description',
        'current_bid',
        'start_amount',
        'maximum_amount',
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
