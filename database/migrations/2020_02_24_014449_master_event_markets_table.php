<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MasterEventMarketsTable extends Migration
{
    protected $masterEventMarketsTable = 'master_event_markets';
    protected $masterEventMarketLinksTable = 'master_event_market_links';
    protected $masterEventMarketLogsTable = 'master_event_market_logs';


    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->masterEventMarketsTable)) {
            Schema::create($this->masterEventMarketsTable, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('master_event_unique_id', 30);
                $table->integer('odd_type_id');
                $table->string('master_event_market_unique_id', 100);
                $table->boolean('is_main')->default(true);
                $table->enum('market_flag', [
                    'HOME',
                    'DRAW',
                    'AWAY'
                ]);
                $table->timestamps();
                $table->foreign('master_event_unique_id')
                    ->references('master_event_unique_id')
                    ->on('master_events')
                    ->onUpdate('cascade');
                $table->foreign('odd_type_id')
                    ->references('id')
                    ->on('odd_types')
                    ->onUpdate('cascade');
                $table->unique('master_event_market_unique_id');
            });
        }

        if (!Schema::hasTable($this->masterEventMarketLinksTable)) {
            Schema::create($this->masterEventMarketLinksTable, function (Blueprint $table) {
                $table->bigInteger('event_market_id');
                $table->bigInteger('master_event_market_id');
            });
        }

        if (!Schema::hasTable($this->masterEventMarketLogsTable)) {
            Schema::create($this->masterEventMarketLogsTable, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('master_event_market_id');
                $table->integer('odd_type_id');
                $table->double('odds');
                $table->string('odd_label', 10);
                $table->boolean('is_main')->default(true);
                $table->enum('market_flag', [
                    'HOME',
                    'DRAW',
                    'AWAY'
                ]);
                $table->timestamps();
                $table->foreign('master_event_market_id')
                    ->references('id')
                    ->on('master_event_markets')
                    ->onUpdate('cascade');
                $table->foreign('odd_type_id')
                    ->references('id')
                    ->on('odd_types')
                    ->onUpdate('cascade');
                $table->index('odd_type_id');
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
        Schema::dropIfExists($this->masterEventMarketLinksTable);
        Schema::dropIfExists($this->masterEventMarketLogsTable);
        Schema::dropIfExists($this->masterEventMarketsTable);
    }
}
