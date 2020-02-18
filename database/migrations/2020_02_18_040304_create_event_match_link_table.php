<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventMatchLinkTable extends Migration
{
    protected $tablename = 'event_match_links';

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
                $table->string('uid');
                $table->integer('master_league_id');
                $table->string('home_team');
                $table->string('away_team');
                $table->timestamps();
                $table->foreign('master_league_id')
                    ->references('id')
                    ->on('master_leagues')
                    ->onUpdate('cascade');
                $table->index('home_team');
                $table->index('away_team');
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
