<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Artisan, Schema};

class AlterTableProvidersAddColumnCurrencyId extends Migration
{
    protected $tablename = "providers";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            $table->integer('currency_id')->default(1);

            $table->foreign('currency_id')
                ->references('id')
                ->on('currency')
                ->onUpdate('cascade');
        });

        Artisan::call('db:seed', [
            '--class' => ProvidersCurrencySeeder::class
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
        });

        Schema::table($this->tablename, function (Blueprint $table) {
            $table->dropColumn('currency_id');
        });
    }
}
