<?php

namespace App\Http\Controllers;

use App\Models\{Provider, SportOddType, UserConfiguration, UserSportOddConfiguration};

use Illuminate\Http\Request;
use Exception;

class UserController extends Controller
{
    /**
     * Get the authenticated User
     *
     * @return [boolean]    status
     * @return [integer]    status_code
     * @return [json]       object
     */
    public function user(Request $request)
    {
        $settings = [];

        $configurations = [
            'general',
            'trade-page',
            'bet-slip',
            'bookies',
            'bet-columns',
            'notifications-and-sounds',
            'language',
        ];

        $providers = Provider::getActiveProviders()
            ->get()
            ->toArray();

        $sportOddTypes = SportOddType::getEnabledSportOdds();

        foreach ($configurations AS $config) {
            $settings[$config] = config('default_config.' . $config);

            if (in_array($config, ['general', 'trade-page', 'bet-slip', 'notifications-and-sounds', 'language'])) {
                $settings = UserConfiguration::getUserConfigByMenu(auth()->user()->id, $config, $settings);
            } else {
                $settings = UserConfiguration::getUserConfigBookiesAndBetColumns($settings);
            }
        }

        return response()->json(
            [
                'status'            => true,
                'status_code'       => 200,
                'data'              => $request->user(),
                'providers'         => $providers,
                'sport_odd_types'   => $sportOddTypes,
                'configuration'     => $settings,
            ]
        );
    }
}
