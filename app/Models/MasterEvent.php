<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Events extends Model
{
    protected $table = "events";

    protected $fillable = [
        'master_event_unique_id',
        'master_league_id',
        'master_team_home_id',
        'master_team_away_id',
        'sport_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
