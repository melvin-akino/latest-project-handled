<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterEventMarketLink extends Model
{
    protected $table = "master_event_market_links";

    protected $fillable = [
        'event_market_id',
        'master_event_market_unique_id'
    ];

    protected $primaryKey = null;

    public $timestamps = false;
    public $incrementing = false;

}
