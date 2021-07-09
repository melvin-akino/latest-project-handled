<?php

namespace App\Models;

use App\Exceptions\ServerException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Exception;

class UserConfiguration extends Model
{
    protected $table = "user_configurations";

    protected $fillable = [
        'user_id',
        'type',
        'value',
        'menu'
    ];

    public static function getUserConfig($user_id)
    {
        return self::where('user_id', $user_id);
    }

    public static function getUserConfigByMenu(int $user_id, string $menu, array &$settings)
    {
        $userConfigurations = self::where('user_id', $user_id)
            ->where('menu', $menu)->get()->toArray();

        $excludeConfigForOverride = [
            'price_formats',
            'trade_layouts',
            'sort_events',
            'adaptive_selections',
            'languages',
        ];

        array_map(
            function ($userConfig) use (&$settings, $menu, $excludeConfigForOverride) {
                if (!in_array($userConfig['type'], $excludeConfigForOverride)) {
                    $settings[$menu][$userConfig['type']] = $userConfig['value'];
                }
            },
            $userConfigurations
        );

        return $settings;
    }

    public static function getUserConfigBookiesAndBetColumns(array &$settings): array
    {
        $getInactiveProviders = UserProviderConfiguration::getInactiveProviders()
            ->get()
            ->toArray();
        $settings['bookies']['disabled_bookies'] = array_column($getInactiveProviders, 'provider_id');

        $getInactiveProviders = UserSportOddConfiguration::getInactiveSportOdds()
            ->get()
            ->toArray();
        $settings['bet-columns']['disabled_columns'] = array_column($getInactiveProviders, 'sport_odd_type_id');

        return $settings;
    }

    public static function saveSettings(string $type, array $request): bool
    {
        try {
            DB::beginTransaction();

            $menus = [
                'general'                   => ['price_format', 'timezone'],
                'trade-page'                => [
                    'suggested',
                    'trade_background',
                    'hide_comp_names_in_fav',
                    'live_position_values',
                    'hide_exchange_only',
                    'trade_layout',
                    'sort_event'
                ],
                'bet-slip'                  => [
                    'use_equivalent_bets',
                    'offers_on_exchanges',
                    'adv_placement_opt',
                    'bets_to_fav',
                    'adv_betslip_info',
                    'tint_bookies',
                    'adaptive_selection',
                    'awaiting_placement_msg'
                ],
                'notifications-and-sounds'  => [
                    'bet_confirm',
                    'site_notifications',
                    'popup_notifications',
                    'order_notifications',
                    'event_sounds',
                    'order_sounds'
                ],
                'language'                  => ['language'],
            ];

            foreach ($menus[$type] as $configType) {
                self::updateOrCreate(
                    [
                        'user_id'   => auth()->user()->id,
                        'type'      => $configType,
                        'menu'      => $type
                    ],
                    [
                        'value'     => $request[$configType]
                    ]
                );
            }

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw new ServerException(trans('generic.db-transaction-error'));
        }
    }
}
