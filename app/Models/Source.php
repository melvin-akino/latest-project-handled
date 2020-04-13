<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    protected $table = "sources";

    protected $fillable = [
        'source_name',
    ];

    public static function getIdByName($source_name)
    {
        return self::where('source_name', $source_name)->first()->id;
    }

    public static function getName($id) {
    	return self::where('id', $id)->first()->source_name;
    }
}
