<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterLeague extends Model
{
    protected $table = "master_leagues";

    protected $fillable = [
        'sport_id',
        'multi_league'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public static function getIdByName($name)
    {
        return self::where('multi_league', $name)
            ->first()
            ->id;
    }
}
