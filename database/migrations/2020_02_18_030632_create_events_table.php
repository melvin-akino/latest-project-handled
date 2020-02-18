<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    protected $tablename = 'events';

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
                $table->integer('league_id');
                $table->integer('event_id');
                $table->timestamps();
                $table->foreign('league_id')
                    ->references('id')
                    ->on('leagues')
                    ->onUpdate('cascade');
                $table->index('event_id');
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
