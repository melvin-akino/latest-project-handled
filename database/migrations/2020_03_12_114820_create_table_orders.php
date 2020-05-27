<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableOrders extends Migration
{
    protected $tablename = "orders";

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
            $table->string('market_id', 100)->index();
            $table->string('status', 30)->index();
            $table->string('bet_id', 30)->index();
            $table->text('bet_selection');
            $table->integer('master_event_market_id')->index();
            $table->integer('provider_id')->index();
            $table->integer('sport_id')->index();
            $table->float('odds', 10, 2);
            $table->float('stake', 10, 2);
            $table->float('actual_stake', 10, 2);
            $table->float('to_win', 10, 2);
            $table->float('actual_to_win', 10, 2);
            $table->integer('provider_account_id')->index();
            $table->dateTimeTz('settled_date')->index()->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');

            $table->foreign('master_event_market_id')
                ->references('id')
                ->on('master_event_markets')
                ->onUpdate('cascade');

            $table->foreign('provider_id')
                ->references('id')
                ->on('providers')
                ->onUpdate('cascade');

            $table->foreign('sport_id')
                ->references('id')
                ->on('sports')
                ->onUpdate('cascade');

            $table->foreign('provider_account_id')
                ->references('id')
                ->on('provider_accounts')
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
