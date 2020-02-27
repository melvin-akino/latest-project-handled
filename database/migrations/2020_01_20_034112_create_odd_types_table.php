<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class CreateOddTypesTable extends Migration
{
    protected $tablename = "odd_types";

    public function up()
    {
        if (!Schema::hasTable($this->tablename)) {
            Schema::create($this->tablename, function (Blueprint $table) {
                $table->integerIncrements('id');
                $table->string('type', 30);

                $table->timestamps();
            });

            Artisan::call('db:seed', [
                '--class' => OddTypesSeeder::class
            ]);
        }
    }

    public function down()
    {
        if (Schema::hasTable($this->tablename)) {
            Schema::dropIfExists($this->tablename);
        }
    }
}
