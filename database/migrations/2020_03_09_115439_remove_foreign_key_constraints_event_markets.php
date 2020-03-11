<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveForeignKeyConstraintsEventMarkets extends Migration
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
        if (Schema::hasTable($this->eventMarketsTable)) {
            Schema::table($this->eventMarketsTable, function (Blueprint $table) {
                if (!isset($_SERVER['_PHPUNIT'])) {
                    $table->dropForeign(['master_event_unique_id']);
                }
            });
        }

        if (Schema::hasTable($this->masterEventMarketsTable)) {
            Schema::table($this->masterEventMarketsTable, function (Blueprint $table) {
                if (!isset($_SERVER['_PHPUNIT'])) {
                    $table->dropForeign(['master_event_unique_id']);
                }
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
        if (Schema::hasTable($this->eventMarketsTable)) {
            Schema::table($this->eventMarketsTable, function (Blueprint $table) {
                if (!isset($_SERVER['_PHPUNIT'])) {
                    $table->foreign('master_event_unique_id')
                        ->references('master_event_unique_id')
                        ->on('master_events')
                        ->onUpdate('cascade');
                }
            });
        }

        if (Schema::hasTable($this->masterEventMarketsTable)) {
            Schema::table($this->masterEventMarketsTable, function (Blueprint $table) {
                if (!isset($_SERVER['_PHPUNIT'])) {
                    $table->foreign('master_event_unique_id')
                        ->references('master_event_unique_id')
                        ->on('master_events')
                        ->onUpdate('cascade');
                }
            });
        }

    }
}
