<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class CreateViewSearchSuggestions extends Migration
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
                master_league_name AS data,
                master_league_name AS label
            FROM
                master_leagues

            UNION ALL

            SELECT
                'event' AS type,
                master_event_unique_id AS data,
                CONCAT(master_league_name, ' | ', master_home_team_name, ' VS ', master_away_team_name) AS label
            FROM
                master_events

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
