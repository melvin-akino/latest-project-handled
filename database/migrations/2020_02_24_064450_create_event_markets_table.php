<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventMarketsTable extends Migration
{
    protected $tablename = 'event_markets';

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
                $table->bigInteger('master_event_market_id')->index();
                $table->integer('event_id')->index();
                $table->integer('odd_type_id')->index();
                $table->double('odds');
                $table->string('odd_label', 10);
                $table->string('bet_identifier', 100)->index();
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

                $table->foreign('event_id')
                    ->references('id')
                    ->on('events')
                    ->onUpdate('cascade');

                $table->foreign('odd_type_id')
                    ->references('id')
                    ->on('odd_types')
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
