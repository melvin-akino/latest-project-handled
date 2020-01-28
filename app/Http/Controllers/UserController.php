<?php

namespace App\Http\Controllers;

use App\Models\{Provider, SportOddType, UserConfiguration, UserSportOddConfiguration};

use Illuminate\Http\Request;
use Exception;

class UserController extends Controller
{
    public function sportOddConfigurations()
    {
        try {
            $userSportOddConfiguration = UserSportOddConfiguration::getSportOddConfiguration();
            $userConfig = [];
            $key = 0;
            array_map(function ($config) use (&$userConfig, &$key) {
                if (empty($userConfig[$key])) {
                    $userConfig[$key] = [
                        'sport_id' => $config->sport_id,
                        'sport'    => $config->sport,
                        'odds'     => [
                            [
                                "sport_odd_type_id" => $config->sport_odd_type_id,
                                "odd_type_id"       => $config->odd_type_id,
                                "type"              => $config->type,
                            ]
                        ]
                    ];
                } else if ($userConfig[$key]['sport_id'] == $config->sport_id) {
                    $userConfig[$key]['odds'][] = [
                        "sport_odd_type_id" => $config->sport_odd_type_id,
                        "odd_type_id"       => $config->odd_type_id,
                        "type"              => $config->type,
                    ];
                } else {
                    $key++;
                }
            }, $userSportOddConfiguration);

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $userConfig
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
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
