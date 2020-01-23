<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProviderConfiguration extends Model
{
    protected $table = 'user_provider_configurations';

    protected $fillable = [
        'provider_id',
        'user_id',
        'punter_percentage',
        'active'
    ];

    /** NEW APPROACH */
    public static function saveSettings($type, $request)
    {
        $values = [];
        $menus = [
            'bookies' => [],
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
