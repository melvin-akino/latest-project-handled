<?php

namespace App\Models;

use App\Models\SystemConfiguration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class MasterLeague extends Model
{
    use SoftDeletes;

    protected $table = "master_leagues";

    protected $fillable = [
        'sport_id',
        'name'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public static function getIdByName($name)
    {
        $query = self::where('name', $name);

        if ($query->count() == 0) {
            return false;
        }

        return $query->first()->id;
    }

    public static function getLeaguesBySportAndGameShedule(int $sportId, array $userProviderIds, string $gameSchedule)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        return DB::SELECT("SELECT  ml.name as master_league_name, count(distinct me.id) as match_count  FROM master_leagues ml
                    LEFT JOIN sports s on (s.id=ml.sport_id)
                    LEFT JOIN master_events me on (me.master_league_id=ml.id)
                    LEFT JOIN events e on (e.master_event_id=me.id)
                    WHERE s.id = {$sportId}
                    AND me.deleted_at is null
                    AND e.deleted_at is null
                    AND ml.deleted_at is null
                    AND e.missing_count <= $maxMissingCount
                    AND me.game_schedule = '{$gameSchedule}'
                    AND e.provider_id IN (" . implode(',', $userProviderIds) . ")
                    GROUP BY master_league_name");

    }
}
