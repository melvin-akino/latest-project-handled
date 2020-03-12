<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterTeamLink extends Model
{
    use SoftDeletes;

    protected $table = "master_team_links";

    protected $fillable = [
        'sport_id',
        'master_team_id',
        'provider_id',
        'team_name'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
