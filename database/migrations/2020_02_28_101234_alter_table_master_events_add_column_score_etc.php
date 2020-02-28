<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableMasterEventsAddColumnScoreEtc extends Migration
{
    protected $tablename = "master_events";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            $table->string('score')->nullable();
            $table->integer('home_penalty')->nullable();
            $table->integer('away_penalty')->nullable();
            $table->string('running_time')->nullable();
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
            $table->string('score')->nullable();
            $table->integer('home_penalty')->nullable();
            $table->integer('away_penalty')->nullable();
            $table->string('running_time')->nullable();
        });
    }
}
