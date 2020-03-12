<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterLeagueLink extends Model
{
    use SoftDeletes;

    protected $table = "master_league_links";

    protected $fillable = [
        'sport_id',
        'master_league_id',
        'league_name',
        'provider_id'
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
