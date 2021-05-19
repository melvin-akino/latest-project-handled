<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};

class Events extends Model
{
    use SoftDeletes;

    protected $table = "events";

    protected $fillable = [
        'master_event_id',
        'sport_id',
        'provider_id',
        'event_identifier',
        'league_id',
        'team_home_id',
        'team_away_id',
        'ref_schedule',
        'game_schedule',
        'deleted_at',
        'missing_count'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public static function getEventByMarketId($marketId)
    {
        return DB::table('events as e')
            ->join('event_markets as em', 'em.event_id', 'e.id')
            ->where('em.bet_identifier', $marketId)
            ->select('e.*')
            ->first();
    }

    public static function getByMarketId($marketId)
    {
        var_dump(DB::table('events as e')
        ->join('event_markets as em', 'em.event_id', 'e.id')
        ->where('em.bet_identifier', $marketId)
        ->whereNull('e.deleted_at')
        ->whereNull('em.deleted_at')
        ->select('e.*')->toSql());
        return DB::table('events as e')
            ->join('event_markets as em', 'em.event_id', 'e.id')
            ->where('em.bet_identifier', $marketId)
            ->whereNull('e.deleted_at')
            ->whereNull('em.deleted_at')
            ->select('e.*')
            ->first();
    }
}
