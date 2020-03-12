<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterEventMarketLog extends Model
{
    protected $table = "master_event_market_logs";

    protected $fillable = [
        'master_event_market_id',
        'odd_type_id',
        'odds',
        'odd_label',
        'is_main',
        'market_flag',
        'provider_id',
    ];

    protected $hidden = [];
}
