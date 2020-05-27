<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterTeam extends Model
{
    use SoftDeletes;

    protected $table = "master_teams";

    protected $fillable = [
        'sport_id',
        'name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
