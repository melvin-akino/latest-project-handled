<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUpsertCountTable extends Migration
{
    protected $oddsUpsertCount = 'odds_upsert_counts';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->oddsUpsertCount, function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('type', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->oddsUpsertCount);
    }
}
