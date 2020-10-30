<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTradeView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("DROP VIEW IF EXISTS trade_window;");

        DB::statement("CREATE VIEW trade_window AS
            SELECT
                ml.sport_id, ml.name as master_league_name, ml.id as league_id, s.sport, e.master_event_id,
                me.master_event_unique_id, mth.name as master_home_team_name, mta.name as master_away_team_name,
                me.ref_schedule, me.game_schedule, me.score, me.running_time,
                me.home_penalty, me.away_penalty, null as odd_type_id, '' as master_event_market_unique_id, true as is_main, '' as market_flag,
                '' as type, 0 as odds, '' as odd_label, e.provider_id, '' as bet_identifier, p.alias, NULL as is_market_empty, 0 as missing_count
                FROM master_leagues as ml
                LEFT JOIN sports as s ON s.id = ml.sport_id
                LEFT JOIN master_events as me ON me.master_league_id = ml.id
                JOIN events as e ON e.master_event_id = me.id
                LEFT JOIN master_teams as mth ON mth.id = me.master_team_home_id
                LEFT JOIN master_teams as mta ON mta.id = me.master_team_away_id
                LEFT JOIN providers as p ON p.id = e.provider_id
                WHERE me.deleted_at IS NULL
                AND e.deleted_at IS NULL
                AND ml.deleted_at IS NULL

            UNION

            SELECT
                ml.sport_id, ml.name as master_league_name, ml.id as league_id, s.sport, e.master_event_id,
                me.master_event_unique_id, mth.name as master_home_team_name, mta.name as master_away_team_name,
                me.ref_schedule, me.game_schedule, me.score, me.running_time,
                me.home_penalty, me.away_penalty, mem.odd_type_id, mem.master_event_market_unique_id, mem.is_main, mem.market_flag,
                ot.type, em.odds, em.odd_label, e.provider_id, em.bet_identifier, p.alias, em.deleted_at as is_market_empty, e.missing_count
                FROM master_leagues as ml
                LEFT JOIN sports as s ON s.id = ml.sport_id
                LEFT JOIN master_events as me ON me.master_league_id = ml.id
                JOIN events as e ON e.master_event_id = me.id
                LEFT JOIN master_teams as mth ON mth.id = me.master_team_home_id
                LEFT JOIN master_teams as mta ON mta.id = me.master_team_away_id
                LEFT JOIN master_event_markets as mem ON mem.master_event_id = me.id AND mem.is_main = true
                LEFT JOIN odd_types as ot ON ot.id = mem.odd_type_id
                JOIN event_markets as em ON em.master_event_market_id = mem.id AND em.event_id = e.id AND em.deleted_at IS NULL
                LEFT JOIN providers as p ON p.id = em.provider_id
                WHERE me.deleted_at IS NULL
                AND e.deleted_at IS NULL
                AND ml.deleted_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS trade_window;");
    }
}
