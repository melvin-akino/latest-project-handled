<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableOrders extends Migration
{
    protected $tablename = "orders";
    protected $strings   = [
        'master_event_market_unique_id',
        'market_id',
        'status',
        'bet_id',
    ];
    protected $integers  = [
        'provider_id',
        'sport_id',
    ];
    protected $floats    = [
        'odds',
        'stake',
        'actual_stake',
        'to_win',
        'actual_to_win',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tablename, function (Blueprint $table) {
            $table->bigIncrements('id');

            foreach ($this->strings AS $string) {
                $table->string($string);
            }

            $table->text('bet_selection');

            foreach ($this->integers AS $integer) {
                $table->integer($integer);
            }

            foreach ($this->floats AS $float) {
                $table->float($float, 10, 2);
            }

            $table->dateTime('settled_date');
            $table->timestamps();
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
