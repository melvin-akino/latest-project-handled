<?php

use Illuminate\Database\Migrations\Migration;

class AddOddTypesData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call('db:seed', [
            '--class' => OddTypesAdditionalSeeder::class
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
