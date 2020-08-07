<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, Artisan};

class AddColumnsToOrdersTable extends Migration
{
    protected $ordersTable = 'orders';
    protected $marketScoreTable = 'market_scores';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable($this->marketScoreTable)) {
            Schema::create($this->marketScoreTable, function (Blueprint $table) {
                $table->string('bet_identifier', 100)->unique()->index();
                $table->string('score', 10)->nullable();
            });

            Artisan::call('db:seed', [
                '--class' => MarketScoreSeeder::class
            ]);
        }
        if (Schema::hasTable($this->ordersTable)) {
            Schema::table($this->ordersTable, function (Blueprint $table) {
                $table->enum('market_flag', [
                    'HOME',
                    'DRAW',
                    'AWAY'
                ])->nullable();
                $table->integer('odd_type_id')->nullable();
                $table->string('final_score', 10)->nullable();
                $table->string('master_event_market_unique_id', 100)->nullable();
                $table->string('master_event_unique_id', 100)->nullable();
                $table->string('master_league_name', 100)->nullable();
                $table->string('master_team_home_name', 100)->nullable();
                $table->string('master_team_away_name', 100)->nullable();

                $table->foreign('odd_type_id')
                      ->references('id')
                      ->on('odd_types')
                      ->onUpdate('cascade');
            });

            Artisan::call('db:seed', [
                '--class' => OrderMissingDataFromEventsSeeder::class
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->marketScoreTable);

        if (Schema::hasTable($this->ordersTable)) {
            Schema::table($this->ordersTable, function (Blueprint $table) {
                $table->dropColumn('market_flag');
                $table->dropColumn('odd_type_id');
                $table->dropColumn('final_score');
                $table->dropColumn('master_event_market_unique_id');
                $table->dropColumn('master_event_unique_id');
                $table->dropColumn('master_league_name');
                $table->dropColumn('master_team_home_name');
                $table->dropColumn('master_team_away_name');
            });
        }
    }
}
