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
                $table->integerIncrements('id');
                $table->integer('event_match_link_id');
                $table->integer('event_id');
                $table->foreign('event_match_link_id')
                    ->references('id')
                    ->on('event_match_links')
                    ->onUpdate('cascade');
                $table->foreign('event_id')
                    ->references('id')
                    ->on('events')
                    ->onUpdate('cascade');
                $table->timestamps();
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
