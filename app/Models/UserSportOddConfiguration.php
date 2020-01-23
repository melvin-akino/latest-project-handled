<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSportOddConfiguration extends Model
{
    protected $table = 'user_sport_odd_configurations';

    protected $fillable = [
        'user_id',
        'sport_odd_type_id',
        'active'
    ];

    /** NEW APPROACH */
    public static function saveSettings($type, $request)
    {
        $values = [];
        $menus = [
            'bet-columns' => [],
        ];

        foreach ($menus[$type] AS $menu) {
            $values[$menu] = $request[$menu];
        }

        return self::updateOrCreate([
            'user_id' => auth()->user()->id,
            'menu' => $type
        ], $values);
    }
}
