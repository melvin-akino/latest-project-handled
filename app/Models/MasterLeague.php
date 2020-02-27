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
        $query = self::where('multi_league', $name);

        if ($query->count() == 0) {
            return false;
        }

        return $query->first()->id;
    }
}
