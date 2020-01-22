<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, Artisan};

class CreateSportOddTypeTable extends Migration
{
    protected $tablename = "sport_odd_type";
    protected $sportsTableName = "sports";
    protected $oddTypesTableName = "odd_types";

    public function up()
    {
        if (!Schema::hasTable($this->tablename)) {
            Schema::create($this->tablename, function (Blueprint $table) {
                $table->integerIncrements('id');
                $table->integer('sport_id');
                $table->integer('odd_type_id');
                $table->timestamps();
                $table->foreign('sport_id')
                    ->references('id')
                    ->on($this->sportsTableName)
                    ->onUpdate('cascade');
                $table->foreign('odd_type_id')
                    ->references('id')
                    ->on($this->oddTypesTableName)
                    ->onUpdate('cascade');
                $table->unique(['sport_id', 'odd_type_id']);
            });

            Artisan::call('db:seed', [
                '--class' => SportOddTypeSeeder::class
            ]);
        }
    }

    public function down()
    {
        if (Schema::hasTable($this->tablename)) {
            Schema::disableForeignKeyConstraints();
            Schema::dropIfExists($this->tablename);
            Schema::enableForeignKeyConstraints();
        }
    }
}
