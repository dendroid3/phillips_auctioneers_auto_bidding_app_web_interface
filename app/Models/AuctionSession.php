<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Psy\CodeCleaner\FunctionContextPass;

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
        return $this->hasMany(Vehicle::class);
    }
}
