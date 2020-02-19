<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SportOddType extends Model
{
    protected $table = 'sport_odd_type';

    protected $fillable = [
        'sport_id',
        'odd_type_id'
    ];

    /**
     * @return array
     */
    public static function getEnabledSportOdds($sportId = null)
    {
        $sql = "SELECT sot.id, sport_id, sport, odd_type_id, type, name, home_label, away_label
                    FROM sport_odd_type as sot
                    JOIN sports as s ON s.id = sot.sport_id
                    JOIN odd_types as ot ON ot.id = sot.odd_type_id
                    WHERE s.is_enabled = '1'";

        if (!is_null($sportId)) {
            $sql .= " AND s.id = " . $sportId;
        }

        return DB::select($sql);
    }
}
