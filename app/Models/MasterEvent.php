<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterEvent extends Model
{
    protected $table = "master_events";

    protected $fillable = [
        'sport_id',
        'master_event_unique_id',
        'master_league_name',
        'master_team_home_id',
        'master_team_away_id',
        'ref_schedule',
        'game_schedule'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public static function getActiveEvents(string $field = null, string $operator = null, string $value = null)
    {
        $return = self::where('deleted_at', null);

        if (!empty($field)) {
            $return = $return->where($field, $operator, $value);
        }

        return $return;
    }
}
