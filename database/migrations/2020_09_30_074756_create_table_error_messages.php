<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class CreateTableErrorMessages extends Migration
{
    protected $tablename = "error_messages";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tablename, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('error',255)->unique();
            $table->timestamps();
        });

        Artisan::call('db:seed', [
            '--class' => ErrorMessagesSeeder::class
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
/*
id int autoincrement primary
error varchar(255) unique
created_at timestamp
updated_at timestamp ON Update
deleted_at datetime null