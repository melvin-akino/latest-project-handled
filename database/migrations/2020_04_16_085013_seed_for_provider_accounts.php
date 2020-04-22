<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

class SeedForProviderAccounts extends Migration
{
    public function up()
    {
        Artisan::call('db:seed', [
            '--class' => ProviderAccountSeeder::class
        ]);

        Artisan::call('db:seed', [
            '--class' => ProviderAccountSettingsSeeder::class
        ]);
    }
}
