<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterLeaguePivotTable extends Migration
{
    protected $tablename = 'master_league_links';

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
                $table->integer('master_league_id');
                $table->integer('league_id');

                $table->timestamps();
                $table->foreign('master_league_id')
                    ->references('id')
                    ->on('master_leagues')
                    ->onUpdate('cascade');
                $table->foreign('league_id')
                    ->references('id')
                    ->on('leagues')
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
        Schema::dropIfExists($this->tablename);
    }
}
