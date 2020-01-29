<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, Artisan};

class CreateProvidersTable extends Migration
{
    protected $connection = "pgsql_crm";
    protected $tablename = "providers";

    public function up()
    {
        if (!Schema::connection($this->connection)->hasTable($this->tablename)) {
            Schema::connection($this->connection)->create($this->tablename, function (Blueprint $table) {
                $table->integerIncrements('id');
                $table->string('name', 20);
                $table->string('alias', 10);
                $table->double('punter_percentage')->default(45);
                $table->tinyInteger('priority')->default(1);
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
                $table->index('name');
            });

            Artisan::call('db:seed', [
                '--class' => ProvidersSeeder::class
            ]);
        }
    }

    public function down()
    {
        if (Schema::connection($this->connection)->hasTable($this->tablename)) {
            Schema::connection($this->connection)->dropIfExists($this->tablename);
        }
    }
}
