<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teams extends Model
{
    protected $table = "teams";

    protected $fillable = [
        'teams',
        'provider_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
