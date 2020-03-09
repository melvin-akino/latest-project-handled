<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};

class MasterEvent extends Model
{
    use SoftDeletes;

    protected $table = "master_events";

    protected $fillable = [
        'sport_id',
        'master_event_unique_id',
        'master_league_name',
        'master_home_team_name',
        'master_away_team_name',
        'ref_schedule',
        'game_schedule',
        'score',
        'running_time',
        'home_penalty',
        'away_penalty',
        'deleted_at'
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
