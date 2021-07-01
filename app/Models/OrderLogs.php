<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLogs extends Model
{
    protected $table = "order_logs";

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'provider_id',
        'sport_id',
        'bet_id',
        'bet_selection',
        'status',
        'settled_date',
        'reason',
        'profit_loss',
        'order_id',
    ];

    protected $hidden = [];

}
