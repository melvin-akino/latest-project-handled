<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Models\{MasterEvent, MasterLeague, Sport, UserSelectedLeague, UserWatchlist};

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Exception;

class TradeController extends Controller
{
    /**
     * Fetch Authenticated User's Lists of Open Orders
     *
     * @return json
     */
    public function getUserBetbar()
    {
        try {
            $data = [
                'status'      => true,
                'status_code' => 200,
                'data'        => [
                    "bet_id_20"        => [
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
                        'created_at'  => "2020-02-11 4:20 PM",
                    ],
                    "bet_id_19"        => [
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
     * Add/Remove to Authenticated User's Lists of Favorite Leagues/Events
     *
     * @param  $action "add|remove"
     * @param  $request \Illuminate\Http\Request
     * @return json
     */
    public function postManageWatchlist($action, Request $request)
    {
        try {
            /** TO DO: Include Logic for adding game events to User Watchlist */
            $data = [];
            $lang = "";

            switch ($request->type) {
                case 'league':
                    $leagueId = MasterLeague::getIdByName($request->data);

                    if ($leagueId) {
                        $masterEventUniqueIds = MasterEvent::getActiveEvents('master_league_name', '=',
                            $request->data)->get('master_event_unique_id')->toArray();
                    } else {
                        return response()->json([
                            'status'      => false,
                            'status_code' => 404,
                            'message'     => trans('generic.not-found')
                        ], 404);
                    }
                    break;

                case 'event':
                    $masterEventUniqueIds = MasterEvent::getActiveEvents('master_event_unique_id', '=',
                        $request->data)->get('master_event_unique_id')->toArray();
                    break;
            }

            if ($action == "add") {
                $lang = "added";

                foreach ($masterEventUniqueIds AS $row) {
                    UserWatchlist::create(
                        [
                            'user_id'                => auth()->user()->id,
                            'master_event_unique_id' => $row['master_event_unique_id']
                        ]
                    );
                    app('swoole')->wsTable->set('userWatchlist:' . auth()->user()->id . ':masterEventUniqueId:' . $row['master_event_unique_id'],
                        ['value' => true]);
                }
            }

            if ($action == "remove") {
                $lang = "removed";

                foreach ($masterEventUniqueIds AS $row) {
                    UserWatchlist::where('user_id', auth()->user()->id)
                        ->where('master_event_unique_id', $row['master_event_unique_id'])
                        ->delete();
                    app('swoole')->wsTable->del('userWatchlist:' . auth()->user()->id . ':masterEventUniqueId:' . $row['master_event_unique_id']);
                }
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'message'     => trans('game.watchlist.' . $lang)
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
     * Get Leagues per Authenticated User's default Sport
     *
     * @return json
     */
    public function getInitialLeagues(Request $request)
    {
        try {
            /** Get Authenticated User's Default Initial Sport : Last Sport visited */
            $data = getUserDefault(auth()->user()->id, 'sport');

            /** Temporary Dummy Data */

            $leaguesQuery = DB::table('master_leagues')->where('sport_id', $data['default_sport'])->whereNull('deleted_at')->get();
            $dataSchedule = [
                'inplay' => [],
                'today'  => [],
                'early'  => []
            ];
            foreach ($dataSchedule as $key => $sched) {
                foreach ($leaguesQuery as $league) {
                    $eventTodayCount = DB::table('master_events')
                        ->where('master_league_name', $league->master_league_name)
                        ->where('game_schedule', $key)
                        ->whereNull('deleted_at')
                        ->count();
                    if ($eventTodayCount > 0) {
                        $dataSchedule[$key][$league->master_league_name] = [
                            'name'        => $league->master_league_name,
                            'match_count' => $eventTodayCount
                        ];
                    }
                }
                $dataSchedule[$key] = array_values($dataSchedule[$key]);
            }

            if (!$data['status']) {
                return response()->json([
                    'status'      => false,
                    'status_code' => 400,
                    'message'     => $data['error']
                ]);
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'sport_id'    => $data['default_sport'],
                'data'        => $dataSchedule
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
     * Add/Remove Authenticated User's Selected Sidebar Leagues
     *
     * @param  $request \Illuminate\Http\Request
     * @return json
     */
    public function postManageSidebarLeagues(Request $request)
    {
        try {
            $checkTable = UserSelectedLeague::where('user_id', auth()->user()->id)
                ->where('master_league_name', $request->data);

            if (Sport::find($request->sport_id)) {
                $wsTable = app('swoole')->wsTable;
                $userId = auth()->user()->id;
                if ($checkTable->count() == 0) {
                    $wsTableKey = 'userSelectedLeagues:' . $userId . ':sId:' . $request->sport_id . ':uniqueId:' . uniqid();
                    $wsTable->set($wsTableKey, ['value' => $request->data]);

                    if (env('APP_ENV') != 'production') {
                        Log::debug('WS Table KEY - ' . $wsTableKey);
                        Log::debug('WS TABLE VALUE - ' . $request->data);
                    }

                    UserSelectedLeague::create(
                        [
                            'user_id'            => $userId,
                            'master_league_name' => $request->data
                        ]
                    );
                } else {
                    foreach ($wsTable as $key => $row) {
                        if (strpos($key, 'userSelectedLeagues:' . $userId) === 0 && $row['value'] == $request->data) {
                            $wsTable->del($key);
                            break;
                        }
                    }
                    $checkTable->delete();
                }

                return response()->json([
                    'status'      => true,
                    'status_code' => 200,
                    'message'     => trans('notifications.save.success')
                ], 200);
            } else {
                throw new Exception(trans('generic.internal-server-error'));
            }
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }
}
