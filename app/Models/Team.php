<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;

    protected $table = "teams";

    protected $fillable = [
        'sport_id',
        'master_team_id',
        'provider_id',
        'name'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
