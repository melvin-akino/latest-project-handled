<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventsData extends Model
{
    protected $table = "events_data";

    protected $fillable = [
        'sport_id',
        'provider_id',
        'league_name',
        'home_team_name',
        'away_team_name',
        'ref_schedule',
        'game_schedule',
        'event_identifier',
        'is_matched'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
