<?php

use App\User;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableUsersZipcode extends Migration
{
    protected $tablename = "users";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            $table->string('zipcode', 6)->default("");
        });

        $zipcodes = User::select('id', 'postcode')->get()->toArray();
        collect($zipcodes)
            ->each(function ($row) {
                User::find($row['id'])->update([ 'zipcode' => $row['postcode'] ]);
            });

        Schema::table($this->tablename, function (Blueprint $table) {
            $table->dropColumn('postcode');
        });

        Schema::table($this->tablename, function (Blueprint $table) {
            $table->string('postcode', 6)->option()->default("");
        });

        $zipcodes = User::select('id', 'zipcode')->get()->toArray();
        collect($zipcodes)
            ->each(function ($row) {
                User::find($row['id'])->update([ 'postcode' => $row['zipcode'] ]);
            });

        Schema::table($this->tablename, function (Blueprint $table) {
            $table->dropColumn('zipcode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            $table->string('zipcode', 6)->default("");
        });

        $zipcodes = User::select('id', 'postcode')->get()->toArray();
        collect($zipcodes)
            ->each(function ($row) {
                User::find($row['id'])->update([ 'zipcode' => $row['postcode'] ]);
            });

        Schema::table($this->tablename, function (Blueprint $table) {
            $table->dropColumn('postcode');
        });

        Schema::table($this->tablename, function (Blueprint $table) {
            $table->integer('postcode')->nullable();
        });

        collect($zipcodes)
            ->each(function ($row) {
                User::find($row['id'])->update([ 'zipcode' => $row['postcode'] ]);
            });

        Schema::table($this->tablename, function (Blueprint $table) {
            $table->dropColumn('zipcode');
        });
    }
}
