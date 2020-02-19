<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, Artisan};

class CreateSystemConfigurationsTable extends Migration
{
    protected $tablename = "system_configurations";

    public function up()
    {
        $connection = config('database.crm_default');
        if (!Schema::connection($connection)->hasTable($this->tablename)) {
            Schema::connection($connection)->create($this->tablename, function (Blueprint $table) {
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
        $connection = config('database.crm_default');
        Schema::connection($connection)->dropIfExists($this->tablename);
    }
}
