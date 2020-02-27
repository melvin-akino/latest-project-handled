<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterTeamLinksTable extends Migration
{
    protected $tablename = 'master_team_links';

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
                $table->integer('master_team_id');
                $table->integer('provider_id');
                $table->string('team_name');
                $table->softDeletes();
                $table->timestamps();

                $table->foreign('master_team_id')
                    ->references('id')
                    ->on('master_teams')
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
