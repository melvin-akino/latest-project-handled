<?php

use Illuminate\Database\Migrations\Migration;

class UpdateFt1x1To1x2OddsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call('db:seed', [
            '--class' => OddType1x1To1x2UpdateSeeder::class
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //@Note: No need to rollback this seed
    }
}
