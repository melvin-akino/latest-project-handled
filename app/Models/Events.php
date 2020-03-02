<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Events extends Model
{
    protected $table = "events";

    protected $fillable = [
        'league_name',
        'sport_id',
        'provider_id',
        'event_identifier',
        'home_team_name',
        'away_team_name',
        'ref_schedule',
        'game_schedule'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
