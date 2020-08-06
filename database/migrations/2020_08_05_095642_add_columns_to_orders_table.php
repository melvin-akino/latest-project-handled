<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToOrdersTable extends Migration
{
    protected $ordersTable = 'orders';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->ordersTable)) {
            Schema::table($this->ordersTable, function (Blueprint $table) {
                $table->enum('market_flag', [
                    'HOME',
                    'DRAW',
                    'AWAY'
                ])->nullable();
                $table->string('final_score', 10)->nullable();

                $table->string('master_league_name', 100)->nullable();
                $table->string('master_team_home_name', 100)->nullable();
                $table->string('master_team_away_name', 100)->nullable();

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
        if (Schema::hasTable($this->ordersTable)) {
            Schema::table($this->ordersTable, function (Blueprint $table) {
                $table->dropColumn('market_flag');
                $table->dropColumn('odd_type_id');
                $table->dropColumn('final_score');
                $table->dropColumn('master_league_name');
                $table->dropColumn('master_team_home_name');
                $table->dropColumn('master_team_away_name');
            });
        }
    }
}
