<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTablePasswordResetsAddColumnUpdatedAt extends Migration {
	protected $tablename = "password_resets";

	public function up() {
		Schema::table($this->tablename, function(Blueprint $table){
			$table->integer('id')->autoIncrement();
			$table->timestamp('updated_at')->nullable();
		});
	}

	public function down() {
		Schema::table($this->tablename, function(Blueprint $table){
			$table->dropColumn('updated_at');
		});
	}
}