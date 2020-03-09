<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSelectedLeague extends Model
{
    protected $table = "user_selected_leagues";

    protected $fillable = [
        'user_id',
        'master_league_name',
        'sport_id',
        'game_schedule'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
