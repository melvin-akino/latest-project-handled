<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserConfiguration extends Model
{
    protected $table = "user_configurations";

    protected $fillable = [
        'user_id',
        'type',
        'value',
    ];

    public static function getUserConfig($user_id)
    {
        return self::where('user_id', $user_id);
    }

    public static function getUserConfigByMenu($user_id, $menu)
    {
        return self::where('user_id', $user_id)
            ->where('menu', $menu);
    }

    public static function saveSettings($type, $request)
    {
        $values = [];
        $menus = [
            'general'                   => [ 'price_format', 'timezone' ],
            'trade-page'                => [ 'suggested', 'trade_background', 'hide_comp_names_in_fav', 'live_position_values', 'hide_exchange_only', 'trade_layout', 'sort_event' ],
            'bet-slip'                  => [ 'use_equivalent_bets', 'offers_on_exchanges', 'adv_placement_opt', 'bets_to_fav', 'adv_betslip_info', 'tint_bookies', 'adaptive_selection' ],
            'notifications-and-sounds'  => [ 'bet_confirm', 'site_notifications', 'popup_notifications', 'order_notifications', 'event_sounds', 'order_sounds' ],
            'language'                  => [ 'language' ],
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
