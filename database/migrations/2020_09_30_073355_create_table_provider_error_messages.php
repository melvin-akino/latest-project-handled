<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, Artisan};


class CreateTableProviderErrorMessages extends Migration
{
    protected $tablename = "provider_error_messages";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tablename, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('error_message_id')->index();
            $table->string('message',255)->unique();
            $table->timestamps();

            $table->foreign('error_message_id')
                ->references('id')
                ->on('error_messages')
                ->onUpdate('cascade');
        });
        Artisan::call('db:seed', [
            '--class' => ProviderErorMessage::class
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

