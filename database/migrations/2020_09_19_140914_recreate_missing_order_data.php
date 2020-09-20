<?php

use Illuminate\Database\Migrations\Migration;

class RecreateMissingOrderData extends Migration
{
    protected $emailAddress = 'lc188@npt.com';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Deleting the last code here since that was only run once. This is to avoid conflict with DB rollback
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
