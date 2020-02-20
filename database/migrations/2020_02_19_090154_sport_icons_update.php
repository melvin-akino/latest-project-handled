<?php

use Illuminate\Database\Migrations\Migration;

class SportIconsUpdate extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call('db:seed', [
            '--class' => SportIconsUpdateSeeder::class
        ]);
    }
}
