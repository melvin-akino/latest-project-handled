<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class CreateSportsTable extends Migration
{
    protected $tablename = "sports";

    public function up()
    {
        if (!Schema::hasTable($this->tablename)) {
            Schema::create($this->tablename, function (Blueprint $table) {
                $table->integerIncrements('id');
                $table->string('sport', 50);
                $table->string('details', 50);
                $table->tinyInteger('priority')->default(1);
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
            });

            Artisan::call('db:seed', [
                '--class' => SportsSeeder::class
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
