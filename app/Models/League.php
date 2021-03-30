<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class League extends Model
{
    use SoftDeletes;

    protected $table = "leagues";

    protected $fillable = [
        'sport_id',
        'master_league_id',
        'name',
        'provider_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public static function getIdByName($name, bool $isArray = false)
    {
        if ($isArray) {
            $query = self::whereIn('name', $name);

            return $query->pluck('id');
        } else {
            $query = self::where('name', $name);

            if ($query->count() == 0) {
                return false;
            }

            return $query->first()->id;
        }
    }
}
