<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailMonitoringSession extends Model
{
    protected $fillable = [
        'id',
        'email',
        'password',
        'pid',
        'started_at',
        'ended_at',
        'status'
    ];
}
