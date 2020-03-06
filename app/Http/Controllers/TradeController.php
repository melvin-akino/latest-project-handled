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
    public function getInitialLeagues()
    {
        try {
            /** Get Authenticated User's Default Initial Sport : Last Sport visited */
            $data = getUserDefault(auth()->user()->id, 'sport');

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
                ->where('master_league_name', $request->league_name)
                ->where('game_schedule', $request->schedule);

            $userSelectedLeagueTable = app('swoole')->userSelectedLeaguesTable;
            if (Sport::find($request->sport_id)) {
                $userId = auth()->user()->id;
                $swtKey = 'userId:' . $userId . ':sId:' . $request->sport_id . ':schedule:' . $request->schedule . ':uniqueId:' . uniqid();
                if ($checkTable->count() == 0) {
                    $userSelectedLeagueTable->set($swtKey, [
                        'user_id'     => $userId,
                        'schedule'    => $request->schedule,
                        'league_name' => $request->league_name,
                        'sport_id'    => $request->sport_id
                    ]);

                    UserSelectedLeague::create(
                        [
                            'user_id'            => $userId,
                            'master_league_name' => $request->league_name,
                            'game_schedule'      => $request->schedule,
                            'sport_id'           => $request->sport_id
                        ]
                    );
                } else {
                    foreach ($userSelectedLeagueTable as $key => $row) {
                        if (strpos($key, 'userId:' . $userId . ':sId:' . $request->sport_id . ':schedule:' . $request->schedule) === 0) {
                            if ($row['league_name'] == $request->league_name) {
                                $userSelectedLeagueTable->del($key);
                            }
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

    public function getUserEvents()
    {
        try {
            $data = [];
            $type = [
                'user_watchlist',
                'user_selected',
            ];

            foreach ($type AS $row) {
                $transformed = DB::table('master_leagues as ml')
                    ->join('sports as s', 's.id', 'ml.sport_id')
                    ->join('master_events as me', 'me.master_league_name', 'ml.master_league_name')
                    ->join('master_event_markets as mem', 'mem.master_event_unique_id', 'me.master_event_unique_id')
                    ->join('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                    ->join('master_event_market_links as meml', 'meml.master_event_market_unique_id', 'mem.master_event_market_unique_id')
                    ->join('event_markets as em', 'em.id', 'meml.event_market_id');

                if ($row == 'user_watchlist') {
                    $transformed = $transformed->join('user_watchlist AS uw', 'me.master_event_unique_id', '=', 'uw.master_event_unique_id')
                        ->where('uw.user_id', auth()->user()->id);
                } else {
                    $transformed = $transformed->join('user_selected_leagues AS sl', 'ml.master_league_name', '=', 'sl.master_league_name');
                }

                $transformed = $transformed->whereNull('me.deleted_at')
                    ->select('ml.sport_id', 'ml.master_league_name', 's.sport', //'mll.provider_id',
                        'me.master_event_unique_id', 'me.master_home_team_name', 'me.master_away_team_name',
                        'me.ref_schedule', 'me.game_schedule', 'me.score', 'me.running_time',
                        'me.home_penalty', 'me.away_penalty', 'mem.odd_type_id', 'mem.master_event_market_unique_id', 'mem.is_main', 'mem.market_flag',
                        'ot.type', 'em.odds', 'em.odd_label', 'em.provider_id')
                    ->distinct()->get();

                array_map(function ($transformed) use (&$data, $row) {
                    $mainOrOther = $transformed->is_main ? 'main' : 'other';

                    if (empty($data[$row][$transformed->game_schedule][$transformed->master_league_name][$transformed->master_event_unique_id])) {
                        $data[$row][$transformed->game_schedule][$transformed->master_league_name][$transformed->master_event_unique_id] = [
                            'sport_id'      => $transformed->sport_id,
                            'sport'         => $transformed->sport,
                            'provider_id'   => $transformed->provider_id,
                            'running_time'  => $transformed->running_time,
                            'ref_schedule'  => $transformed->ref_schedule,
                        ];
                    }

                    if (empty($data[$row][$transformed->game_schedule][$transformed->master_league_name][$transformed->master_event_unique_id]['home'])) {
                        $data[$row][$transformed->game_schedule][$transformed->master_league_name][$transformed->master_event_unique_id]['home'] = [
                            'name' => $transformed->master_home_team_name,
                            'score' => empty($transformed->score) ? '' : array_values(explode(' - ', $transformed->score))[0],
                            'redcard' => $transformed->home_penalty
                        ];
                    }

                    if (empty($data[$row][$transformed->game_schedule][$transformed->master_league_name][$transformed->master_event_unique_id]['away'])) {
                        $data[$row][$transformed->game_schedule][$transformed->master_league_name][$transformed->master_event_unique_id]['away'] = [
                            'name' => $transformed->master_away_team_name,
                            'score' => empty($transformed->score) ? '' : array_values(explode(' - ', $transformed->score))[1],
                            'redcard' => $transformed->home_penalty
                        ];
                    }

                    if (empty($data[$row][$transformed->game_schedule][$transformed->master_league_name][$transformed->master_event_unique_id]['market_odds'][$mainOrOther][$transformed->type][$transformed->market_flag])) {
                        $data[$row][$transformed->game_schedule][$transformed->master_league_name][$transformed->master_event_unique_id]['market_odds'][$mainOrOther][$transformed->type][$transformed->market_flag] = [
                            'odds' => (double) $transformed->odds,
                            'market_id' => $transformed->master_event_market_unique_id
                        ];

                        if (!empty($transformed->odd_label)) {
                            $data[$row][$transformed->game_schedule][$transformed->master_league_name][$transformed->master_event_unique_id]['market_odds'][$mainOrOther][$transformed->type][$transformed->market_flag]['points'] = $transformed->odd_label;
                        }
                    }

                }, $transformed->toArray());
            }

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
}
