<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class UserController extends Controller
{
    public function sportOddConfigurations(Request $request)
    {
        try {
            $user = $request->user();
            $sql = "SELECT sport_odd_type_id, sport_id, sport, odd_type_id, type, active
                    FROM user_sport_odd_configurations as usoc
                    JOIN sport_odd_type as sot ON sot.id = usoc.sport_odd_type_id
                    JOIN sports as s ON s.id = sot.sport_id
                    JOIN odd_types as ot ON ot.id = sot.odd_type_id
                    WHERE usoc.user_id = ?";
            $userSportOddConfiguration = DB::select($sql, [$user->id]);

            $defaultUserSportOddConfig = config('constants.user-sport-odd-configuration');
            $userConfiguration = array_map(function ($configuration) use ($userSportOddConfiguration) {
                $userConfig = [];
                array_map(function ($config) use (&$userConfig, $configuration) {
                    if ($configuration['sport_odd_type_id'] == $config->sport_odd_type_id) {
                        $userConfig = [
                            'sport_odd_type_id' => $config->sport_odd_type_id,
                            'odd_type_id'       => $config->odd_type_id,
                            'sport_id'          => $config->sport_id,
                            'sport'             => $config->sport,
                            'type'              => $config->type,
                            'active'            => $config->active
                        ];
                    }
                }, $userSportOddConfiguration);

                if (!empty($userConfig)) {
                    return $userConfig;
                }
                return $configuration;
            }, $defaultUserSportOddConfig);

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $userConfiguration
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
