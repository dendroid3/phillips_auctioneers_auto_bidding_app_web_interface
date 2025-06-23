<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BidStage extends Model
{   
    protected $fillable = [
        'auction_session_id',
        'name',
        'start_time',
        'end_time',
        'increment',
        'status'
    ];

    public function bids ()
    {
        return $this->hasMany(Bid::class);
    }

    public function auction_session ()
    {
        return $this->belongsTo(AuctionSession::class);
    }

    /**
     * Scope to get only active bid stages
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

}
