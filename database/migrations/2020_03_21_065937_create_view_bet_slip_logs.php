<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class CreateViewBetSlipLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE VIEW bet_slip_logs AS
            SELECT
                'ORDER_PLACED' AS \"log_type\",
                ol.user_id AS \"user_id\",
                ol.reason AS \"message\",
                ol.status AS \"status\",
                mem.master_event_market_unique_id AS \"memuid\",
                UPPER(p.alias) AS \"provider\",
                CONCAT(UPPER(p.alias), ': ', o.odds, ' - ', ol.status) AS \"data\",
                ol.created_at AS \"timestamp\"
            FROM
                orders AS o

            JOIN order_logs AS ol
            ON o.id = ol.order_id

            JOIN providers AS p
            ON o.provider_id = p.id

            JOIN master_event_markets AS mem
            ON o.master_event_market_unique_id = mem.master_event_market_unique_id

            UNION ALL

            SELECT
                'PRICE_UPDATE' AS \"log_type\",
                0 AS \"user_id\",
                '' AS \"message\",
                'MARKET_UPDATE' AS \"status\",
                mem.master_event_market_unique_id AS \"memuid\",
                UPPER(p.alias) AS \"provider\",
                CONCAT(meml.odds, ' ', ot.type, ' ', meml.odd_label) AS \"data\",
                meml.created_at AS \"timestamp\"
            FROM
                master_event_markets AS mem

            JOIN master_event_market_logs AS meml
            ON mem.id = meml.master_event_market_id

            JOIN odd_types AS ot
            ON mem.odd_type_id = ot.id

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
