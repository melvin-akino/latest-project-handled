<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMasterEventMarketLinksColumn extends Migration
{
    protected $tablename = "master_event_market_links";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            $table->dropColumn('master_event_market_id');
            $table->string('master_event_market_unique_id', 100)->nullable();
            $table->foreign('master_event_market_unique_id')
                ->references('master_event_market_unique_id')
                ->on('master_event_markets')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            $table->dropColumn('master_event_market_unique_id');
            $table->integer('master_event_market_id')->nullable();
            $table->foreign('master_event_market_id')
                ->references('id')
                ->on('master_event_markets')
                ->onUpdate('cascade');
        });
    }
}
