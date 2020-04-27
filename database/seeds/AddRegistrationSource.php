<?php

use App\Models\Source;
use Illuminate\Database\Seeder;

class AddRegistrationSource extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Source::firstOrCreate([
            'source_name' => "REGISTRATION",
        ]);
    }
}
