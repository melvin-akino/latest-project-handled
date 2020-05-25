<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Game extends Model
{

    public static function updateOddsData(array $marketOdds = [], int $providerId)
    {
        return DB::table('event_markets as em')
                ->join('master_event_markets as mem', 'mem.id', 'em.master_event_market_id')
                ->where('mem.master_event_market_unique_id', $marketOdds['market_id'])
                ->where('em.provider_id', $providerId)
                ->update([
                    'em.odds' => $marketOdds['odds']
                ]);
    }

    public static function getGameDetails(int $masterLeagueId, string $schedule = 'early')
    {
        return DB::table('master_leagues as ml')
                ->join('sports as s', 's.id', 'ml.sport_id')
                ->join('master_events as me', 'me.master_league_id', 'ml.id')
                ->join('master_teams as mth', 'mth.id', 'me.master_team_home_id')
                ->join('master_teams as mta', 'mta.id', 'me.master_team_home_id')
                ->join('master_event_markets as mem', 'mem.master_event_id', 'me.id')
                ->join('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                ->join('event_markets as em', 'em.master_event_market_id', 'mem.id')
                ->select('ml.sport_id', 'ml.name as master_league_name', 's.sport',
                    'me.master_event_unique_id', 'mth.name as master_home_team_name', 'mta.name as master_away_team_name',
                    'me.ref_schedule', 'me.game_schedule', 'me.score', 'me.running_time',
                    'me.home_penalty', 'me.away_penalty', 'mem.odd_type_id', 'mem.master_event_market_unique_id', 'mem.is_main', 'mem.market_flag',
                    'ot.type', 'em.odds', 'em.odd_label', 'em.provider_id', 'em.bet_identifier')
                ->where('ml.id', $masterLeagueId)
                ->where('me.game_schedule', $schedule)
                ->where('mem.is_main', true)
                ->whereNull('me.deleted_at')
                ->distinct()->get();
    }

    public static function getWatchlistGameDetails(int $userId)
    {
        return $transformed = DB::table('master_leagues as ml')
            ->join('sports as s', 's.id', 'ml.sport_id')
            ->join('master_events as me', 'me.master_league_id', 'ml.id')
            ->join('master_event_markets as mem', 'mem.master_event_id', 'me.id')
            ->join('master_teams as mth', 'mth.id', 'me.master_team_home_id')
            ->join('master_teams as mta', 'mta.id', 'me.master_team_home_id')
            ->join('odd_types as ot', 'ot.id', 'mem.odd_type_id')
            ->join('event_markets as em', 'em.master_event_market_id', 'mem.id')
            ->join('user_watchlist as uw', 'uw.master_event_id', 'me.id')
            ->select('ml.sport_id', 'ml.name as master_league_name', 's.sport',
                'me.master_event_unique_id', 'mth.name as master_home_team_name', 'mta.name as master_away_team_name',
                'me.ref_schedule', 'me.game_schedule', 'me.score', 'me.running_time',
                'me.home_penalty', 'me.away_penalty', 'mem.odd_type_id', 'mem.master_event_market_unique_id',
                'mem.is_main', 'mem.market_flag',
                'ot.type', 'em.odds', 'em.odd_label', 'em.provider_id')
            ->where('uw.user_id', $userId)
            ->where('mem.is_main', true)
            ->distinct()->get();
    }
}
