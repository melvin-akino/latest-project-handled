<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timezones extends Model
{
	protected $table = "timezones";

    protected $fillable = [
    	'name',
    	'timezone',
    ];
}
