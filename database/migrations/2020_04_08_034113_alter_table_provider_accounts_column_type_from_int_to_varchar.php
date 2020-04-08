<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class AlterTableProviderAccountsColumnTypeFromIntToVarchar extends Migration
{
    protected $tablename = "provider_accounts";
    protected $column = "type";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            $table->string($this->column)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE " . $this->tablename . " ALTER COLUMN " . $this->column . " TYPE integer USING (trim(" . $this->column . ")::integer);");
    }
}
