<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterLeaguesTable extends Migration
{
    protected $tablename = 'master_leagues';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tablename)) {
            Schema::create($this->tablename, function (Blueprint $table) {
                $table->integerIncrements('id');
                $table->integer('sport_id');
                $table->string('multi_league');
                $table->timestamps();
                $table->foreign('sport_id')
                    ->references('id')
                    ->on('sports')
                    ->onUpdate('cascade');
                $table->index('multi_league');
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
        Schema::dropIfExists($this->tablename);
    }
}
