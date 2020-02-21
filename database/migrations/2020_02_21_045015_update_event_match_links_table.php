<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEventMatchLinksTable extends Migration
{
    protected $tablename = 'event_match_links';
    protected $newTableName = 'master_events';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tablename)) {
            Schema::rename($this->tablename, $this->newTableName);
            Schema::table($this->newTableName, function (Blueprint $table) {
                $table->renameColumn('uid', 'master_event_unique_id');
                $table->integer('master_team_home_id')->nullable();
                $table->integer('master_team_away_id')->nullable();
                $table->foreign('master_team_home_id')
                    ->references('id')
                    ->on('master_teams')
                    ->onUpdate('cascade');
                $table->foreign('master_team_away_id')
                    ->references('id')
                    ->on('master_teams')
                    ->onUpdate('cascade');
            });

            Schema::table($this->newTableName, function (Blueprint $table) {
                $table->dropColumn('home_team');
            });

            Schema::table($this->newTableName, function (Blueprint $table) {
                $table->dropColumn('away_team');
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
        if (Schema::hasTable($this->newTableName)) {
            Schema::rename($this->newTableName, $this->tablename);
            Schema::table($this->tablename, function (Blueprint $table) {
                $table->dropColumn('master_team_home_id');
                $table->dropColumn('master_team_away_id');
                $table->string('home_team', 100);
                $table->string('away_team', 100);
                $table->index('home_team');
                $table->index('away_team');
                $table->renameColumn('master_event_unique_id', 'uid');
            });
        }
    }
}
