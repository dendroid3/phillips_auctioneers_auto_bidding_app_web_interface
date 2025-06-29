<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Psy\CodeCleaner\FunctionContextPass;
use App\Events\AuctionSessionCreatedEvent;

class AuctionSession extends Model
{
    protected $fillable = [
        'id',
        'title',
        'date',
        'vehicles_url',
        'status',
        'start_time',
        'end_time',
    ];

    public function vehicles ()
    {
        return $this->hasMany(Vehicle::class)->orderBy('updated_at', 'desc');
    }

    public function bidStages ()
    {
        return $this->hasMany(BidStage::class);
    }

     protected $dispatchesEvents = [
        'saved' => AuctionSessionCreatedEvent::class,
    ];
}
