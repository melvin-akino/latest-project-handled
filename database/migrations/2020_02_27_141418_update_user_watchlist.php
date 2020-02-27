<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateUserWatchlist extends Migration
{
    protected $tableName = 'user_watchlist';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('master_event_id');
                $table->string('master_event_unique_id', 30);
                $table->foreign('master_event_unique_id')
                    ->references('master_event_unique_id')
                    ->on('master_events')
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
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('master_event_unique_id');
                $table->integer('master_event_id');
            });
        }
    }
}
