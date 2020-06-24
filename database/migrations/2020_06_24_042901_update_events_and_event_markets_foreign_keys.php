<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEventsAndEventMarketsForeignKeys extends Migration
{
    protected $eventsTable   = 'events';
    protected $eventMarketsTable = 'event_markets';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->eventsTable)) {
            Schema::table($this->eventsTable, function (Blueprint $table) {
                $table->integer('master_event_id')->nullable()->change();
            });
        }

        if (Schema::hasTable($this->eventMarketsTable)) {
            Schema::table($this->eventMarketsTable, function (Blueprint $table) {
                $table->integer('master_event_market_id')->nullable()->change();

            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No turning back
    }
}
