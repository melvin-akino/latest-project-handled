<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProviderErrorMessageId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            //
            $table->integer('provider_error_message_id')->nullable();

              $table->foreign('provider_error_message_id')
                ->references('id')
                ->on('provider_error_messages')
                ->onUpdate('cascade');
       
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            //
            $table->dropColumn('provider_error_message_id');
        });
    }
}
