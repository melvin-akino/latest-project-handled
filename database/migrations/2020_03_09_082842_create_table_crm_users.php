<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Artisan, Schema};

class CreateTableCrmUsers extends Migration
{
    protected $tablename = "crm_users";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tablename, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->integer('status_id')->default(1);
            $table->text('email')->unique;
            $table->text('password');
            $table->text('remember_token')->nullable();
            $table->timestamps();
        });

        Artisan::call('db:seed', [
            '--class' => AdminUserSeeder::class
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
