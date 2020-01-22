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

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public static function getAll()
    {
        return self::all();
    }
}
