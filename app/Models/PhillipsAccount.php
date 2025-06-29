<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhillipsAccount extends Model
{
    protected $fillable = [
        "user_id",
        "email",
        "account_password",
        "account_status",
        "email_app_password",
        "email_status",
        "last_email_update",
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
