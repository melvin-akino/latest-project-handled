<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateTradeTables extends Migration
{
    protected $eventTableName = 'events';
    protected $masterEventsTableName = 'master_events';
    protected $eventTeamLinksTable = 'event_team_links';
    protected $eventMarketsTable = 'event_markets';
    protected $masterEventMarketsTable = 'master_event_markets';
    protected $masterEventMarketLinksTable = 'master_event_market_links';
    protected $masterEventMarketLogsTable = 'master_event_market_logs';


    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->eventTableName)) {
            Schema::table($this->eventTableName, function (Blueprint $table) {
                $table->integer('team_home_id')->nullable();
                $table->integer('team_away_id')->nullable();
                $table->integer('provider_id')->default(1);
                $table->integer('sport_id')->default(1);
                $table->string('reference_schedule', 30)->default('');
                $table->foreign('team_home_id')
                    ->references('id')
                    ->on('teams');
                $table->foreign('team_away_id')
                    ->references('id')
                    ->on('teams');
                $table->foreign('provider_id')
                    ->references('id')
                    ->on('providers');
                $table->foreign('sport_id')
                    ->references('id')
                    ->on('sports');
            });
        }

        if (Schema::hasTable($this->masterEventsTableName)) {
            Schema::table($this->masterEventsTableName, function (Blueprint $table) {
                $table->integer('sport_id')->default(1);
                $table->integer('provider_id')->default(1);
                $table->string('reference_schedule', 30)->default('');
                $table->foreign('sport_id')
                    ->references('id')
                    ->on('sports');
                $table->foreign('provider_id')
                    ->references('id')
                    ->on('sports');
            });
        }

        if (Schema::hasTable($this->eventTeamLinksTable)) {
            Schema::drop($this->eventTeamLinksTable);
        }

        if (Schema::hasTable($this->eventMarketsTable)) {
            Schema::table($this->eventMarketsTable, function (Blueprint $table) {
                $table->bigInteger('id')->change();
            });

            if (DB::getDriverName() !== 'sqlite') {
                Schema::table($this->eventMarketsTable, function (Blueprint $table) {
                    $table->dropForeign(['master_event_unique_id']);
                });
            }
            Schema::table($this->eventMarketsTable, function (Blueprint $table) {
                $table->renameColumn('master_event_unique_id', 'event_id');
            });
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE " . $this->eventMarketsTable . " ALTER COLUMN event_id  TYPE integer USING (event_id::integer);");
            }

            Schema::table($this->eventMarketsTable, function (Blueprint $table) {
                $table->foreign('event_id')
                    ->references('id')
                    ->on('events');
            });
        }

        if (!Schema::hasTable($this->masterEventMarketsTable)) {
            Schema::create($this->masterEventMarketsTable, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('master_event_unique_id', 30);
                $table->integer('odd_type_id');
                $table->string('master_event_market_unique_id', 100);
                $table->boolean('is_main')->default(true);
                $table->enum('market_flag', [
                    'HOME',
                    'DRAW',
                    'AWAY'
                ]);
                $table->timestamps();
                $table->foreign('master_event_unique_id')
                    ->references('master_event_unique_id')
                    ->on('master_events')
                    ->onUpdate('cascade');
                $table->foreign('odd_type_id')
                    ->references('id')
                    ->on('odd_types')
                    ->onUpdate('cascade');
                $table->unique('master_event_market_unique_id');
            });
        }

        if (!Schema::hasTable($this->masterEventMarketLinksTable)) {
            Schema::create($this->masterEventMarketLinksTable, function (Blueprint $table) {
                $table->bigInteger('event_market_id');
                $table->bigInteger('master_event_market_id');
            });
        }

        if (!Schema::hasTable($this->masterEventMarketLogsTable)) {
            Schema::create($this->masterEventMarketLogsTable, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('master_event_market_id');
                $table->integer('odd_type_id');
                $table->double('odds');
                $table->string('odd_label', 10);
                $table->boolean('is_main')->default(true);
                $table->enum('market_flag', [
                    'HOME',
                    'DRAW',
                    'AWAY'
                ]);
                $table->timestamps();
                $table->foreign('master_event_market_id')
                    ->references('id')
                    ->on('master_event_markets')
                    ->onUpdate('cascade');
                $table->foreign('odd_type_id')
                    ->references('id')
                    ->on('odd_types')
                    ->onUpdate('cascade');
                $table->index('odd_type_id');
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
        if (Schema::hasTable($this->eventTableName)) {
            Schema::table($this->eventTableName, function (Blueprint $table) {
                $table->dropColumn(['team_home_id', 'team_away_id', 'provider_id', 'reference_schedule', 'sport_id']);
            });
        }

        if (Schema::hasTable($this->masterEventsTableName)) {
            Schema::table($this->masterEventsTableName, function (Blueprint $table) {
                $table->dropColumn(['sport_id', 'reference_schedule', 'provider_id']);
            });
        }

        if (!Schema::hasTable($this->eventTeamLinksTable)) {
            Schema::create($this->eventTeamLinksTable, function (Blueprint $table) {
                $table->integer('event_id');
                $table->integer('team_id');
                $table->enum('team_flag', ['HOME', 'AWAY']);
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

        if (Schema::hasTable($this->eventMarketsTable)) {
            Schema::table($this->eventMarketsTable, function (Blueprint $table) {
                $table->integer('id')->change();
            });

            if (DB::getDriverName() !== 'sqlite') {
                Schema::table($this->eventMarketsTable, function (Blueprint $table) {
                    $table->dropForeign(['event_id']);
                });
            }

            Schema::table($this->eventMarketsTable, function (Blueprint $table) {
                $table->renameColumn('event_id', 'master_event_unique_id');
            });

            Schema::table($this->eventMarketsTable, function (Blueprint $table) {
                $table->string('master_event_unique_id', 30)->change();
                $table->foreign('master_event_unique_id')
                    ->references('master_event_unique_id')
                    ->on($this->masterEventsTableName);

            });
        }

        Schema::dropIfExists($this->masterEventMarketLinksTable);
        Schema::dropIfExists($this->masterEventMarketLogsTable);
        Schema::dropIfExists($this->masterEventMarketsTable);
    }
}
