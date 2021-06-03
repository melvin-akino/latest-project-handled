<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};

class MasterEvent extends Model
{
    use SoftDeletes;

    protected $table = "master_events";

    protected $fillable = [
        'sport_id',
        'master_event_unique_id',
        'master_league_id',
        'master_team_home_id',
        'master_team_away_id',
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

    public static function getByMasterParams($eventData)
    {
        return DB::table('master_events')
                 ->where('master_league_id', $eventData['master_league_id'])
                 ->where('master_team_home_id', $eventData['master_team_home_id'])
                 ->where('master_team_away_id', $eventData['master_team_away_id'])
                 ->where('ref_schedule', $eventData['ref_schedule'])
                 ->whereNull('deleted_at');
    }

    public static function getMasterEvent($eventId)
    {
        return DB::table('master_events as me')
                ->join('event_groups as eg', 'eg.master_event_id', 'me.id')
                ->join('events as e', 'eg.event_id', 'e.id')
                ->where('me.master_event_unique_id', $eventId)
                ->first();
    }
}
