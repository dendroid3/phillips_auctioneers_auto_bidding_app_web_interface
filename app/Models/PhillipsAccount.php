<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhillipsAccount extends Model
{
    protected $fillable = [
        "user_id",
        "email",
        "password",
        "status"
    ];

    public function user ()  
    {
        return $this->belongsTo(User::class);
    }

    public function bids () 
    {
        return $this->hasMany(Bid::class);
    }

    public function vehicles () 
    {
        return $this->hasMany(Vehicle::class);
    }
}
