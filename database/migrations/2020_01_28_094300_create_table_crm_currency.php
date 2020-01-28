<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableCrmCurrency extends Migration
{
    protected $connection = "pgsql_crm";
    protected $tablename = "currency";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->tablename, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50);
            $table->char('code', 5);
            $table->char('symbol', 5);
            $table->timestamps();
        });

        Artisan::call('db:seed', [
            '--class' => CRMCurrencySeeder::class
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists($this->tablename);
    }
}
