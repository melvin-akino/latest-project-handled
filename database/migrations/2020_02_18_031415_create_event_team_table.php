<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventTeamTable extends Migration
{
    protected $tablename = 'event_team';
    protected $teamFlags = [
        'HOME',
        'AWAY'
    ];

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
                $table->integer('event_id');
                $table->integer('team_id');
                $table->enum('team_flag', $this->teamFlags);
                $table->timestamps();
                $table->foreign('event_id')
                    ->references('id')
                    ->on('events')
                    ->onUpdate('cascade');
                $table->foreign('team_id')
                    ->references('id')
                    ->on('teams')
                    ->onUpdate('cascade');
                $table->index('team_flag');
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
