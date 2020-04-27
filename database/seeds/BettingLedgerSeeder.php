<?php

use App\Models\Source;
use Illuminate\Database\Seeder;

class BettingLedgerSeeder extends Seeder
{
    protected $sources = [
        'BET_WIN',
        'BET_LOSE',
        'BET_HALF_WIN',
        'BET_HALF_LOSE',
        'RETURN_STAKE',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->sources AS $source) {
            Source::create(
                [ 'source_name' => $source ]
            );
        }
    }
}
