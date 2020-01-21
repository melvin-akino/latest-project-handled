<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSportOddConfigurationsTable extends Migration
{
    protected $tablename = "user_sport_odd_configurations";
    protected $userTablename = "users";
    protected $sportOddTypeTablename = "sport_odd_type";

    public function up()
    {
        if (!Schema::hasTable($this->tablename)) {
            Schema::create($this->tablename, function (Blueprint $table) {
                $table->integerIncrements('id');
                $table->integer('user_id');
                $table->integer('sport_odd_type_id');
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->foreign('user_id')
                    ->references('id')
                    ->on($this->userTablename)
                    ->onUpdate('cascade');
                $table->foreign('sport_odd_type_id')
                    ->references('id')
                    ->on($this->sportOddTypeTablename)
                    ->onUpdate('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists($this->tablename);
    }
}
