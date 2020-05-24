<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class CreateViewSearchSuggestionsV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE VIEW search_suggestions AS
            SELECT
                'league' AS type,
                name AS data,
                name AS label
            FROM
                master_leagues

            UNION ALL

            SELECT
                'event' AS type,
                me.master_event_unique_id AS data,
                CONCAT(ml.name, ' | ', mth.name, ' VS ', mta.name) AS label
            FROM
                master_events AS me

            JOIN master_leagues AS ml
            ON me.master_league_id = ml.id

            JOIN master_teams AS mth
            ON me.master_team_home_id = mth.id

            JOIN master_teams AS mta
            ON me.master_team_away_id = mta.id

            ORDER BY label ASC;
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS search_suggestions;");
    }
}
