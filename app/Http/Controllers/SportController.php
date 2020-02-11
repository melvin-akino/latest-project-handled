<?php

namespace App\Http\Controllers;

use App\Models\SportOddType;

use Illuminate\Http\Request;
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
    }
}
