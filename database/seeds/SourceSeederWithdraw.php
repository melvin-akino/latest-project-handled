<?php

use Illuminate\Database\Seeder;
use App\Models\Source;
class SourceSeederWithdraw extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
         Source::create([ 'source_name' => 'WITHDRAW' ]);
    }
}
