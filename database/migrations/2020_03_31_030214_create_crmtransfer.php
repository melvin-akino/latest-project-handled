<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrmtransfer extends Migration
{
    protected $tablename = "crmtransfer";
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tablename, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->float('transfer_amount', 15, 2);
            $table->integer('currency_id');
            $table->integer('crm_user_id');
            $table->text('reason');
            $table->integer('user_id');
            $table->timestamps();
        });

         Artisan::call('db:seed', [
            '--class' => SourceSeederWithdraw::class
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tablename);
    }
}
