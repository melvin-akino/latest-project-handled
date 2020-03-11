<?php

namespace App\Http\Controllers;

use App\Models\{
    EventMarket,
    MasterEvent,
    MasterEventMarket,
    OddType,
    Sport
};
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    /**
     * Get Event Details from the given parameter to display information
     * in the Bet Slip Interface
     *
     * @param  string $memUID
     * @return json
     */
    public function getEventMarketsDetails(string $memUID)
    {
        $masterEventMarket = MasterEventMarket::where('master_event_market_unique_id', $memUID);

        if (!$masterEventMarket->exists()) {
            return response()->json([
                'status'      => false,
                'status_code' => 404,
                'message'     => trans('generic.not-found')
            ], 404);
        }

        $masterEventMarket = $masterEventMarket->first([
            'is_main',
            'market_flag',
            'odd_type_id',
            'master_event_unique_id'
        ]);

        $masterEvent = MasterEvent::where('master_event_unique_id', $masterEventMarket->master_event_unique_id);

        if (!$masterEvent->exists()) {
            return response()->json([
                'status'      => false,
                'status_code' => 404,
                'message'     => trans('generic.not-found')
            ], 404);
        }

        $masterEvent = $masterEvent->first();

        $data = [
            'league_name'   => $masterEvent->master_league_name,
            'home'          => $masterEvent->master_home_team_name,
            'away'          => $masterEvent->master_away_team_name,
            'game_schedule' => $masterEvent->game_schedule,
            'ref_schedule'  => $masterEvent->ref_schedule,
            'running_time'  => $masterEvent->running_time,
            'score'         => $masterEvent->score,
            'home_penalty'  => $masterEvent->home_penalty,
            'away_penalty'  => $masterEvent->away_penalty,
            'market_flag'   => $masterEventMarket->market_flag,
            'odd_type'      => OddType::getTypeByID($masterEventMarket->odd_type_id),
            'sport'         => Sport::getNameByID($masterEvent->sport_id),
        ];

        return response()->json([
            'status'      => true,
            'status_code' => 200,
            'data'        => $data
        ], 200);
    }
}
