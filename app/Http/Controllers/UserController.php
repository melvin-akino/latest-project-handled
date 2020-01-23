<?php

namespace App\Http\Controllers;

use App\Models\{Provider, SportOddType, UserConfiguration, UserSportOddConfiguration};

use Illuminate\Http\Request;
use Exception;

class UserController extends Controller
{
    public function sportOddConfigurations(Request $request)
    {
        try {
            $userSportOddConfiguration = UserSportOddConfiguration::getSportOddConfiguration();

            $defaultUserSportOddConfig = config('constants.user-sport-odd-configuration');
            $userConfiguration = array_map(
                function ($configuration) use ($userSportOddConfiguration) {
                    $userConfig = [];
                    array_map(
                        function ($config) use (&$userConfig, $configuration) {
                            if ($configuration['sport_odd_type_id'] == $config->sport_odd_type_id) {
                                $userConfig = [
                                    'sport_odd_type_id' => $config->sport_odd_type_id,
                                    'odd_type_id' => $config->odd_type_id,
                                    'sport_id' => $config->sport_id,
                                    'sport' => $config->sport,
                                    'type' => $config->type,
                                    'active' => $config->active
                                ];
                            }
                        },
                        $userSportOddConfiguration
                    );

                    if (!empty($userConfig)) {
                        return $userConfig;
                    }
                    return $configuration;
                },
                $defaultUserSportOddConfig
            );

            return response()->json(
                [
                    'status' => true,
                    'status_code' => 200,
                    'data' => $userConfiguration
                ],
                200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'status' => false,
                    'status_code' => 500,
                    'message' => trans('generic.internal-server-error')
                ],
                500
            );
        }
    }

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
                'status' => true,
                'status_code' => 200,
                'data' => $request->user(),
                'providers' => $providers,
                'sport_odd_types' => $sportOddTypes,
                'configuration' => $settings,
            ]
        );
    }
}
