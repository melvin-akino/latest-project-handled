<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterLeague extends Model
{
    protected $table = "leagues";

    protected $fillable = [
        'sport_id',
        'multi_league',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
