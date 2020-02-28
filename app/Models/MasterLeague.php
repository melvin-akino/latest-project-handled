<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterLeague extends Model
{
    protected $table = "master_leagues";

    protected $fillable = [
        'sport_id',
        'master_league_name'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public static function getIdByName($name)
    {
        $query = self::where('master_league_name', $name);

        if ($query->count() == 0) {
            return false;
        }

        return $query->first()->id;
    }
}
