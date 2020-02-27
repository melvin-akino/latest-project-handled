<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, Artisan};

class CreateSystemConfigurationsTable extends Migration
{
    protected $tablename = "system_configurations";

    public function up()
    {
        if (!Schema::hasTable($this->tablename)) {
            Schema::create($this->tablename, function (Blueprint $table) {
                $table->integerIncrements('id');
                $table->string('type', 100);
                $table->string('value', 100);
                $table->timestamps();
                $table->unique('type');
            });

            Artisan::call('db:seed', [
                '--class' => SystemConfigurationSeeder::class
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists($this->tablename);
    }
}
