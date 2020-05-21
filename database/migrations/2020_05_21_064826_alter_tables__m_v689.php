<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTablesMV689 extends Migration
{
    protected $em   = "event_markets";
    protected $us   = "user_selected_leagues";
    protected $mem  = "master_event_markets";
    protected $mel  = "master_event_links";
    protected $meml = "master_event_market_links";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // EVENT MARKET :: START
            Schema::table($this->em, function (Blueprint $table) {
                $table->integer('master_event_id')->index()->nullable();

                $table->index('game_schedule');

                $table->foreign('master_event_id')
                    ->references('id')
                    ->on('master_events')
                    ->onUpdate('cascade');
            });
        // EVENT MARKET :: END

        // MASTER EVENT MARKETS :: START
            Schema::table($this->mem, function (Blueprint $table) {
                $table->integer('master_event_id')->index()->nullable();

                $table->index('master_event_market_unique_id');
                $table->index('odd_type_id');

                $table->foreign('master_event_id')
                    ->references('id')
                    ->on('master_events')
                    ->onUpdate('cascade');
            });
        // MASTER EVENT MARKETS :: END

        // MASTER EVENT LINKS :: START
            Schema::table($this->mel, function (Blueprint $table) {
                $table->dropColumn('master_event_unique_id');
                $table->integer('master_event_id')->index()->nullable();

                $table->foreign('master_event_id')
                    ->references('id')
                    ->on('master_events')
                    ->onUpdate('cascade');
            });
        // MASTER EVENT LINKS :: END

        // MASTER EVENT MARKET LINKS :: START
            Schema::table($this->meml, function (Blueprint $table) {
                $table->dropColumn('master_event_market_unique_id');
                $table->integer('master_event_market_id')->index()->nullable();
            });
        // MASTER EVENT MARKET LINKS :: END
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // EVENT MARKET :: START
            Schema::table($this->em, function (Blueprint $table) {
                $table->dropForeign([ 'master_event_id' ]);

                $table->dropIndex($this->em . '_game_schedule_index');
            });

            Schema::table($this->em, function (Blueprint $table) {
                $table->dropColumn('master_event_id');
            });
        // EVENT MARKET :: END

        // MASTER EVENT MARKETS :: START
            Schema::table($this->mem, function (Blueprint $table) {
                $table->dropForeign([ 'master_event_id' ]);

                $table->dropIndex($this->mem . '_master_event_market_unique_id_index');
                $table->dropIndex($this->mem . '_odd_type_id_index');
            });

            Schema::table($this->mem, function (Blueprint $table) {
                $table->dropColumn('master_event_id');
            });
        // MASTER EVENT MARKETS :: END

        // MASTER EVENT LINKS :: START
            Schema::table($this->mel, function (Blueprint $table) {
                $table->dropForeign([ 'master_event_id' ]);
            });

            Schema::table($this->mel, function (Blueprint $table) {
                $table->dropColumn('master_event_id');
                $table->string('master_event_unique_id')->nullable();
            });
        // MASTER EVENT LINKS :: END

        // MASTER EVENT MARKET LINKS :: START
            Schema::table($this->meml, function (Blueprint $table) {
                $table->dropColumn('master_event_market_id');
                $table->string('master_event_market_unique_id')->nullable();
            });
        // MASTER EVENT MARKET LINKS :: END
    }
}
