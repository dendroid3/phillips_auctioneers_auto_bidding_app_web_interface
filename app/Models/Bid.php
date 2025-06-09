<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    protected $fillable = [
        'vehicle_id',
        'phillips_account_id',
        'bid_stage_id',
        'status',
        'amount'
    ];

    public function bidStage ()
    {
        return $this->belongsTo(BidStage::class);
    }

    public function vehicle ()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
