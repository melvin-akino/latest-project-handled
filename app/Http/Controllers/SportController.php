<?php

namespace App\Http\Controllers;

use App\Models\{Sport, SportOddType};
use Illuminate\Support\Facades\Log;
use Exception;

class SportController extends Controller
{
    public function configurationOdds()
    {
        try {
            $userSportOddConfiguration = SportOddType::getEnabledSportOdds();
            $userConfig = [];
            array_map(function ($config) use (&$userConfig) {
                if (empty($userConfig[$config->sport_id])) {
                    $userConfig[$config->sport_id] = [
                        'sport_id' => $config->sport_id,
                        'sport'    => $config->sport
                    ];
                }
                $userConfig[$config->sport_id]['odds'][] = [
                    "sport_odd_type_id" => $config->id,
                    "odd_type_id"       => $config->odd_type_id,
                    "type"              => $config->type,
                    'name'              => $config->name,
                    'home_label'        => $config->home_label,
                    'away_label'        => $config->away_label
                ];
            }, $userSportOddConfiguration);

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => array_values($userConfig)
            ]);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "SportController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /**
     * Fetch Active Sports Categories being catered by Multiline Application.
     *
     * @return json
     */
    public function getSports()
    {
        try {
            $sports = Sport::orderBy('priority', 'asc')
                ->where('is_enabled', true)
                ->get([
                    'id',
                    'icon',
                    'priority',
                    'sport'
                ]);
            $userSport = getUserDefault(auth()->user()->id, 'sport');
            return response()->json([
                'status'        => true,
                'status_code'   => 200,
                'data'          => $sports,
                'default_sport' => $userSport['default_sport']
            ]);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "SportController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }
}
