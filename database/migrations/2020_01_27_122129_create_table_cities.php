<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableCities extends Migration
{
    protected $tablename = "cities";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tablename, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('city_name');
            $table->integer('state_id');
            $table->integer('country_id');
            $table->timestamps();

            $table->foreign('state_id')
                ->references('id')
                ->on('states')
                ->onUpdate('cascade');

            $table->index([
                'city_name',
                'state_id',
                'country_id',
            ]);
        });

        Artisan::call('db:seed', [
            '--class' => CitiesSeeder::class
        ]);
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
