<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSportOddConfiguration extends Model
{
    protected $table = 'user_sport_odd_configurations';

    protected $fillable = [
        'user_id',
        'sport_odd_type_id',
        'active'
    ];
}
