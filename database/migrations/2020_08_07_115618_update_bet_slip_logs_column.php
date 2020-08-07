<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBetSlipLogsColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("DROP VIEW IF EXISTS bet_slip_logs;");

        DB::statement("CREATE VIEW bet_slip_logs AS
            SELECT
                'ORDER_PLACED' AS \"log_type\",
                ol.user_id AS \"user_id\",
                ol.reason AS \"message\",
                ol.status AS \"status\",
                o.master_event_market_unique_id AS \"memuid\",
                UPPER(p.alias) AS \"provider\",
                o.odds,
                CONCAT(UPPER(p.alias), ': ', o.odds, ' - ', ol.status) AS \"data\",
                ol.created_at AS \"timestamp\"
            FROM
                orders AS o

            JOIN order_logs AS ol
            ON o.id = ol.order_id

            JOIN providers AS p
            ON o.provider_id = p.id

            UNION ALL

            SELECT
                'PRICE_UPDATE' AS \"log_type\",
                0 AS \"user_id\",
                '' AS \"message\",
                'MARKET_UPDATE' AS \"status\",
                meml.master_event_market_unique_id AS \"memuid\",
                UPPER(p.alias) AS \"provider\",
                meml.odds,
                CONCAT(meml.odds, ' ', ot.type, ' ', meml.odd_label) AS \"data\",
                meml.created_at AS \"timestamp\"
            FROM
                master_event_market_logs AS meml

            JOIN odd_types AS ot
            ON meml.odd_type_id = ot.id

            JOIN providers AS p
            ON meml.provider_id = p.id;
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS bet_slip_logs;");
    }
}
