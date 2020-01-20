<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\OddType;
use Carbon\Carbon;

class CreateOddTypesTable extends Migration
{
    protected $tablename = "odd_types";

    public function up()
    {
        if (!Schema::hasTable($this->tablename)) {
            Schema::create($this->tablename, function (Blueprint $table) {
                $table->integerIncrements('id');
                $table->string('type', 30);
                $table->timestamps();
            });

            $oddTypes = [
                'FT 1X1',
                'FT Handicap',
                'FT O/U',
                'FT O/E',
                '1H 1X2',
                '1H Handicap',
                '1H O/U'
            ];

            foreach ($oddTypes as $key => $type) {
                $oddType = new OddType();
                $oddType->type = $type;
                $oddType->created_at = Carbon::now();
                $oddType->updated_at = Carbon::now();
                $oddType->save();
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable($this->tablename)) {
            Schema::dropIfExists($this->tablename);
        }
    }
}
