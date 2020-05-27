<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableOrderLogs extends Migration
{
    protected $tablename = "order_logs";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tablename, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->index();
            $table->integer('provider_id')->index();
            $table->integer('sport_id')->index();
            $table->string('bet_id', 30)->index();
            $table->text('bet_selection');
            $table->string('status', 30)->index();
            $table->dateTimeTz('settled_date')->index()->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');

            $table->foreign('provider_id')
                ->references('id')
                ->on('providers')
                ->onUpdate('cascade');

            $table->foreign('sport_id')
                ->references('id')
                ->on('sports')
                ->onUpdate('cascade');
        });
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
