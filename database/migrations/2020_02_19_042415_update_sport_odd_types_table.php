<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSportOddTypesTable extends Migration
{
    protected $tablename = 'sport_odd_type';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tablename)) {
            Schema::table($this->tablename, function (Blueprint $table) {
                $table->string('name', 30)->default('');
                $table->string('home_label', 30)->default('');
                $table->string('away_label', 30)->default('');
                $table->index('name');
            });

            Artisan::call('db:seed', [
                '--class' => SportOddTypeUpdateSeeder::class
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable($this->tablename)) {
            Schema::table($this->tablename, function (Blueprint $table) {
                $table->dropColumn('name');
                $table->dropColumn('home_label');
                $table->dropColumn('away_label');
            });
        }
    }
}
