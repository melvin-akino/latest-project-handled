<?php

use App\Models\Source;
use Illuminate\Database\Seeder;

class TimedOutSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Source::updateOrCreate(
            [ 'source_name' => "BET_TIMED_OUT"],
            []
        );
    }
}
