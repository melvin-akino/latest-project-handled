<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IndexMarketFlag extends Migration
{
    protected $eventMarketsTable = 'event_markets';
    protected $masterEventMarketsTable = 'master_event_markets';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->eventMarketsTable, function (Blueprint $table) {
            $table->index('market_flag');
        });

        Schema::table($this->masterEventMarketsTable, function (Blueprint $table) {
            $table->index('market_flag');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->eventMarketsTable, function (Blueprint $table) {
            $table->dropIndex(['market_flag']);
        });

        Schema::table($this->masterEventMarketsTable, function (Blueprint $table) {
            $table->dropIndex(['market_flag']);
        });
    }
}
