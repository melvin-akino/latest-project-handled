<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventMatchLinksTable extends Migration
{
    protected $tablename = 'master_event_links';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tablename)) {
            Schema::create($this->tablename, function (Blueprint $table) {
                $table->integer('event_id');
                $table->integer('master_event_id');
                $table->timestamps();

                $table->foreign('master_event_id')
                    ->references('id')
                    ->on('master_events')
                    ->onUpdate('cascade');

                $table->foreign('event_id')
                    ->references('id')
                    ->on('events')
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
