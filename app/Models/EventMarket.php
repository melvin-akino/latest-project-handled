<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};

class EventMarket extends Model
{
    use SoftDeletes;

    protected $table = "event_markets";

    protected $fillable = [
        'master_event_unique_id',
        'odd_type_id',
        'odds',
        'odd_label',
        'bet_identifier',
        'is_main',
        'market_flag',
        'provider_id',
        'event_identifier',
        'deleted_at',
    ];
}
