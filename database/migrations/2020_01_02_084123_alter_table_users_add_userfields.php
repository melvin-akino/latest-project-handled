<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableUsersAddUserfields extends Migration {
	protected $tablename = "users";
	protected $str = [
		'firstname',
		'lastname',
		'phone',
	];
	protected $text = [
		'address',
	];
	protected $int = [
		'country',
		'state',
		'city',
		'postcode',
		'phone_country_code',
		'odds_type',
		'currency_id',
	];
	protected $date = [
		'birthdate',
	];

	public function up() {
		Schema::table($this->tablename, function(Blueprint $table){
			// STRINGS
			foreach($this->str AS $row) {
				$table->string($row)->nullable();
			}

			// TEXTS
			foreach($this->text AS $row) {
				$table->text($row)->nullable();
			}

			// INTEGERS
			foreach($this->int AS $row) {
				$table->integer($row)->nullable();
			}

			// DATES
			foreach($this->date AS $row) {
				$table->date($row)->nullable();
			}
		});
	}

	public function down() {
		Schema::table($this->tablename, function(Blueprint $table){
			// STRINGS
			foreach($this->str AS $row) {
				$table->dropColumn($row);
			}

			// TEXTS
			foreach($this->text AS $row) {
				$table->dropColumn($row);
			}

			// INTEGERS
			foreach($this->int AS $row) {
				$table->dropColumn($row);
			}

			// DATES
			foreach($this->date AS $row) {
				$table->dropColumn($row);
			}
		});
	}
}