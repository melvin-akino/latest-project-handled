<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProviderConfigurationsTable extends Migration
{
    protected $tableName = "user_provider_configurations";
    protected $userTableName = "users";
    protected $providersTableName = "providers";

    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->integerIncrements('id');
                $table->integer('user_id');
                $table->integer('provider_id');
                $table->double('punter_percentage')->default(45);
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->foreign('user_id')
                    ->references('id')
                    ->on($this->userTableName)
                    ->onUpdate('cascade');
                $table->foreign('provider_id')
                    ->references('id')
                    ->on($this->providersTableName)
                    ->onUpdate('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
}
