<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignkeyprovidererror extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('provider_error_messages', function (Blueprint $table) {
            //
            $table->foreign('error_message_id')
                ->references('id')
                ->on('error_messages')
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
        Schema::table('provider_error_messages', function (Blueprint $table) {
            //
            $table->dropForeign(['error_message_id']);
        });
    }
}
