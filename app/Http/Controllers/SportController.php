<?php

namespace App\Http\Controllers;

use App\Models\SportOddType;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Exception;

class SportController extends Controller
{
    public function configurationOdds()
    {
        try {
            $userSportOddConfiguration = SportOddType::getEnabledSportOdds();
            $userConfig = [];
            $key = 0;
            array_map(function ($config) use (&$userConfig, &$key) {
                if (empty($userConfig[$key])) {
                    $userConfig[$key] = [
                        'sport_id' => $config->sport_id,
                        'sport'    => $config->sport,
                        'odds'     => [
                            [
                                "sport_odd_type_id" => $config->id,
                                "odd_type_id"       => $config->odd_type_id,
                                "type"              => $config->type,
                            ]
                        ]
                    ];
                } else if ($userConfig[$key]['sport_id'] == $config->sport_id) {
                    $userConfig[$key]['odds'][] = [
                        "sport_odd_type_id" => $config->id,
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

    public function getUserBetbar()
    {
        try {
            $data = [
                'status'      => true,
                'status_code' => 200,
                'data'        => [
                    [
                        'league_name' => "FIFA Asia 2020",
                        'home'        => "Vietnam",
                        'away'        => "South Korea",
                        'bet_info'    => [
                            'home',
                            'FT 1X2',
                            '1.54',
                            '120'
                        ],
                        'status'      => "Processing",
                        'analytics'   => [],
                        'tracker'     => [],
                        'created_at'  => "2020-02-11 4:20 PM",
                    ],
                    [
                        'league_name' => "FIFA Asia 2020",
                        'home'        => "Philippines",
                        'away'        => "India",
                        'bet_info'    => [
                            'away',
                            'FT 1X2',
                            '2.58',
                            '80'
                        ],
                        'status'      => "Success",
                        'analytics'   => [],
                        'tracker'     => [],
                        'created_at'  => "2020-02-11 4:10 PM",
                    ],
                ],
            ];

            return response()->json($data, 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /**
     * Fetch Authenticated User's Lists of Favorite League Events
     *
     * @return json
     */
    public function getUserWatchlist()
    {
        try {
            /** TO DO: Include Logic for fetching User Watchlist game events */

            $data = [
                "Football League A" => [
                    [
                        "uid"            => "20200312-1-1",
                        "game_schedule"  => "3",
                        "home_team_name" => "Los Angeles Lakers",
                        "away_team_name" => "Los Angeles Clippers",
                        "ft_1x2"         => [
                            "home" => 1.37,
                            "away" => 4.90,
                            "draw" => 5.24
                        ],
                        "ft_hdp"         => [
                            "home" => 1.63,
                            "away" => 1.13
                        ],
                        "ft_ou"          => [
                            "home" => 5.41,
                            "away" => 3.31
                        ]
                    ],
                    [
                        "uid"            => "20200312-1-2",
                        "game_schedule"  => "3",
                        "home_team_name" => "Cleveland Cavaliers",
                        "away_team_name" => "Indiana Pacers",
                        "ft_1x2"         => [
                            "home" => 3.15,
                            "away" => 0.55,
                            "draw" => 1.25
                        ],
                        "ft_hdp"         => [
                            "home" => 0.19,
                            "away" => 0.81
                        ],
                        "ft_ou"          => [
                            "home" => 3.85,
                            "away" => 1.23
                        ]
                    ],
                ],
                "Football League B" => [
                    [
                        "uid"            => "20200312-2-1",
                        "game_schedule"  => "2",
                        "home_team_name" => "Chicago Bulls",
                        "away_team_name" => "Miami Heat",
                        "ft_1x2"         => [
                            "home" => 1.85,
                            "away" => 0.33,
                            "draw" => 2.70
                        ],
                        "ft_hdp"         => [
                            "home" => 3.70,
                            "away" => 5.94
                        ],
                        "ft_ou"          => [
                            "home" => 3.84,
                            "away" => 4.96
                        ]
                    ]
                ]
            ];

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $data
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /**
     * Add to Authenticated User's Lists of Favorite League Events
     *
     * @return json
     */
    public function postAddToWatchlist(Request $request)
    {
        try {
            /** TO DO: Include Logic for adding game events to User Watchlist */

            $data = [];

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'message'     => trans('game.watchlist.success')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /**
     * Remove to Authenticated User's Lists of Favorite League Events
     *
     * @return json
     */
    public function postRemoveToWatchlist(Request $request)
    {
        try {
            /** TO DO: Include Logic for removing game events to User Watchlist */

            $data = [];

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'message'     => trans('game.watchlist.removed')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }
}
