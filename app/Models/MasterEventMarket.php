<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterEventMarket extends Model
{
    protected $table = "master_event_markets";

    protected $fillable = [
        'master_event_unique_id',
        'odd_type_id',
        'master_event_unique_market_id',
        'is_main',
        'market_flag',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
