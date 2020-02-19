<?php

use Illuminate\Database\Migrations\Migration;

class UpdateOddTypesSeed extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call('db:seed', [
            '--class' => OddTypesUpdateSeeder::class
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
