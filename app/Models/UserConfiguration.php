<?php

namespace App\Models;

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
            'price_format',
            'price_format_user',
            'trade_layout',
            'trade_layout_user',
            'sort_event',
            'sort_event_user',
            'adaptive_selection',
            'adaptive_selection_user',
            'disabled_bookies',
            'languages',
            'language_user'
        ];

        $configTypes = array_column($userConfigurations, 'type');

        switch ($menu) {
            case 'general':
                $configKey = array_search('price_format', $configTypes);
                $settings[$menu]['price_format_user'] = $userConfigurations[$configKey]['value'];

            case 'trade-page':
                $configKey = array_search('trade_layout', $configTypes);
                $settings[$menu]['trade_layout_user'] = $userConfigurations[$configKey]['value'];
                $configKey = array_search('sort_event', $configTypes);
                $settings[$menu]['sort_event_user'] = $userConfigurations[$configKey]['value'];

            case 'bet-slip':
                $configKey = array_search('adaptive_selection', $configTypes);
                $settings[$menu]['adaptive_selection_user'] = $userConfigurations[$configKey]['value'];

            case 'language':
                $configKey = array_search('languages', $configTypes);
                $settings[$menu]['language_user'] = $userConfigurations[$configKey]['value'];

            default:
                array_map(
                    function ($userConfig) use (&$settings, $menu, $excludeConfigForOverride) {
                        if (!in_array($userConfig['type'], $excludeConfigForOverride)) {
                            $settings[$menu][$userConfig['type']] = $userConfig['value'];
                        }
                    },
                    $userConfigurations
                );
                break;
        }

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
                'general' => ['price_format', 'timezone'],
                'trade-page' => [
                    'suggested',
                    'trade_background',
                    'hide_comp_names_in_fav',
                    'live_position_values',
                    'hide_exchange_only',
                    'trade_layout',
                    'sort_event'
                ],
                'bet-slip' => [
                    'use_equivalent_bets',
                    'offers_on_exchanges',
                    'adv_placement_opt',
                    'bets_to_fav',
                    'adv_betslip_info',
                    'tint_bookies',
                    'adaptive_selection'
                ],
                'notifications-and-sounds' => [
                    'bet_confirm',
                    'site_notifications',
                    'popup_notifications',
                    'order_notifications',
                    'event_sounds',
                    'order_sounds'
                ],
                'language' => ['language'],
            ];

            foreach ($menus[$type] as $configType) {
                self::updateOrCreate(
                    [
                        'user_id' => auth()->user()->id,
                        'type' => $configType,
                        'menu' => $type
                    ],
                    [
                        'value' => $request[$configType]
                    ]
                );
            }

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            return false;
        }
    }
}
