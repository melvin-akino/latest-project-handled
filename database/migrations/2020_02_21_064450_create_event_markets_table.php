<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventMarketsTable extends Migration
{
    protected $tablename = 'event_markets';

    protected $masterEventsTable = 'master_events';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->masterEventsTable)) {
            Schema::table($this->masterEventsTable, function (Blueprint $table) {
                $table->string('master_event_unique_id', 30)->change();
                $table->unique('master_event_unique_id');
            });
        }
        if (!Schema::hasTable($this->tablename)) {
            Schema::create($this->tablename, function (Blueprint $table) {
                $table->integerIncrements('id');
                $table->string('master_event_unique_id', 30);
                $table->integer('odd_type_id');
                $table->double('odds');
                $table->string('odd_label', 10);
                $table->string('bet_identifier', 100);
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
                $table->unique('master_event_unique_id');
                $table->index('odd_type_id');
                $table->index('bet_identifier');
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
        if (Schema::hasTable($this->masterEventsTable)) {
            Schema::table($this->masterEventsTable, function (Blueprint $table) {
                $table->dropUnique(['master_event_unique_id']);
            });
        }
    }
}
