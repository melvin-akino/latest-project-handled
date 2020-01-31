<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, Artisan};

class CreateTableCountries extends Migration
{
    protected $tablename = "countries";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tablename, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('country_name');
            $table->char('country_code', 3);
            $table->timestamps();

            $table->index([
                'country_name',
                'country_code',
            ]);
        });

        Artisan::call('db:seed', [
            '--class' => CountriesSeeder::class
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
