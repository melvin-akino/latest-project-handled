<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = "cities";

    protected $fillable = [
        'city_name',
        'state_id',
        'country_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
