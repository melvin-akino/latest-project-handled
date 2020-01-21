<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SportOddType extends Model
{
    protected $table = 'sport_odd_type';

    protected $fillable = [
        'sport_id',
        'odd_type_id',
        'created_at',
        'updated_at'
    ];
}
