<?php

use App\Models\OddType;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TableOddTypeAlterHandicapValues extends Migration
{
    protected $renames = [
        'FT Handicap' => 'FT HDP',
        '1H Handicap' => '1H HDP',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->renames AS $type => $rename) {
            OddType::where('type', $type)
                ->update([
                    'type' => $rename
                ]);
        }
    }
}
