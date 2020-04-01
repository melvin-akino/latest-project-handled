<?php

use App\Models\Source;
use Illuminate\Database\Seeder;

class AddPlaceBetToSource extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Source::create(
            [ 'source_name' => "PLACE_BET" ]
        );
    }
}
