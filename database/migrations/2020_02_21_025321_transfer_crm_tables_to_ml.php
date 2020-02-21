<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, Artisan};

class TransferCrmTablesToMl extends Migration
{
    protected $tablename = "providers";
    protected $currencyTable = "currency";
    protected $systemConfigurationsTable = "system_configurations";

    public function up()
    {
        $connection = config('database.crm_default');
        if (!Schema::hasTable($this->tablename)) {
            Schema::create($this->tablename, function (Blueprint $table) {
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
                '--class' => ProvidersMlSeeder::class
            ]);
        }

        if (!Schema::hasTable($this->currencyTable)) {
            Schema::create($this->currencyTable, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 50);
                $table->char('code', 5);
                $table->char('symbol', 5);
                $table->timestamps();
            });

            Artisan::call('db:seed', [
                '--class' => CurrencySeeder::class
            ]);
        }

        if (!Schema::hasTable($this->systemConfigurationsTable)) {
            Schema::create($this->systemConfigurationsTable, function (Blueprint $table) {
                $table->integerIncrements('id');
                $table->string('type', 100);
                $table->string('value', 100);
                $table->timestamps();
                $table->unique('type');
            });

            Artisan::call('db:seed', [
                '--class' => SystemConfigurationMlSeeder::class
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists($this->tablename);
        Schema::dropIfExists($this->tablename);
        Schema::dropIfExists($this->tablename);
    }
}
