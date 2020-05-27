<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MasterEventMarketsTable extends Migration
{
    protected $masterEventMarketsTable = 'master_event_markets';
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
                $table->integer('master_event_id')->index();
                $table->integer('odd_type_id')->index();
                $table->string('master_event_market_unique_id', 100)->unique();
                $table->boolean('is_main')->default(true);
                $table->enum('market_flag', [
                    'HOME',
                    'DRAW',
                    'AWAY'
                ]);
                $table->timestamps();
                $table->foreign('master_event_id')
                    ->references('id')
                    ->on('master_events')
                    ->onUpdate('cascade');

                $table->foreign('odd_type_id')
                    ->references('id')
                    ->on('odd_types')
                    ->onUpdate('cascade');
            });
        }

        if (!Schema::hasTable($this->masterEventMarketLogsTable)) {
            Schema::create($this->masterEventMarketLogsTable, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('master_event_market_id')->index();
                $table->integer('odd_type_id')->index();
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
        Schema::dropIfExists($this->masterEventMarketLogsTable);
        Schema::dropIfExists($this->masterEventMarketsTable);
    }
}
