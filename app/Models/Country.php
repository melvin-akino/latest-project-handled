<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = "cities";

    protected $fillable = [
        'country_name',
        'country_code',
        'phonecode',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
