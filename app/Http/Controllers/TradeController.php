<?php

namespace App\Http\Controllers;

use App\Models\{
    Game,
    MasterEventMarket,
    Order,
    MasterEvent,
    MasterLeague,
    UserSelectedLeague,
    UserWatchlist,
    UserConfiguration,
    Timezones,
    UserProviderConfiguration,
    OddType
};
use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;
use App\Facades\SwooleHandler;
use App\Http\Requests\ToggleLeaguesRequest;

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
            $userTz        = "Etc/UTC";
            $getUserConfig = UserConfiguration::getUserConfig(auth()->user()->id)
                                              ->where('type', 'timezone')
                                              ->first();

            if (!is_null($getUserConfig)) {
                $userTz = Timezones::find($getUserConfig->value)->name;
            }

            $betBarData = Order::getBetBarData(auth()->user()->id);
            $ouLabels   = OddType::where('type', 'LIKE', '%OU%')->pluck('id')->toArray();
            $data       = [];
            foreach ($betBarData as $betData) {
                $proceed = false;
                if ($betData->status != 'FAILED') {
                    $proceed = true;
                } else {
                    $currentTime = Carbon::now()->toDateTimeString();
                    $expireTime  = Carbon::parse($betData->created_at)->addSeconds($betData->order_expiry)->toDateTimeString();
                    if ($currentTime <= $expireTime) {
                        $proceed = true;
                    } else {
                        $proceed = false;
                    }
                }

                if ($proceed) {
                    if (empty($betData->current_score)) {
                        $currentScore = "0 - 0";
                    } else {
                        $currentScore = $betData->current_score;
                    }

                    $ou = "";
                    if (in_array($betData->odd_type_id, $ouLabels)) {
                        $ou = explode(' ', $betData->odd_label);
                        $ou = $ou[0] == "O" ? "Over " . $ou[1] : "Under " . $ou[1];
                    }

                    $score = explode(" - ", $currentScore);

                    $data[] = [
                        'order_id'       => $betData->order_id,
                        'provider_alias' => $betData->alias,
                        'event_id'       => $betData->master_event_unique_id,
                        'market_id'      => $betData->master_event_market_unique_id,
                        'odd_type_id'    => $betData->odd_type_id,
                        'league_name'    => $betData->master_league_name,
                        'home'           => $betData->master_team_home_name,
                        'away'           => $betData->master_team_away_name,
                        'bet_info'       => [
                            $betData->market_flag,
                            $betData->name,
                            $betData->odds,
                            $betData->stake,
                            $betData->odd_label,
                            $betData->market_flag == 'HOME' ? $betData->master_team_home_name : $betData->master_team_away_name,
                            $ou
                        ],
                        'home_score'     => $score[0],
                        'away_score'     => $score[1],
                        'score_on_bet'   => $betData->score_on_bet,
                        'status'         => $betData->status,
                        'created_at'     => Carbon::createFromFormat("Y-m-d H:i:s", $betData->created_at, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s"),
                    ];
                }
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => array_slice($data, 0, 5)
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "TradeController",
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
                    $leagueId = MasterLeague::getLeagueDetailsByName($request->data)->id;

                    if ($leagueId) {
                        $masterEventUniqueIds = MasterEvent::getActiveEvents('master_league_id', '=', $leagueId)
                                                           ->get(['id', 'master_event_unique_id'])
                                                           ->toArray();
                    } else {
                        $toLogs = [
                            "class"       => "TradeController",
                            "message"     => trans('generic.not-found'),
                            "module"      => "API_ERROR",
                            "status_code" => 404,
                        ];
                        monitorLog('monitor_api', 'error', $toLogs);

                        return response()->json([
                            'status'      => false,
                            'status_code' => 404,
                            'message'     => trans('generic.not-found')
                        ], 404);
                    }
                    break;

                case 'event':
                    $masterEventUniqueIds = MasterEvent::getActiveEvents('master_event_unique_id', '=', $request->data)
                                                       ->get(['id', 'master_event_unique_id'])
                                                       ->toArray();
                    break;
            }

            if ($action == "add") {
                $lang = "added";
                foreach ($masterEventUniqueIds as $row) {
                    UserWatchlist::create(
                        [
                            'user_id'         => auth()->user()->id,
                            'master_event_id' => $row['id']
                        ]
                    );
                    app('swoole')->userWatchlistTable->set('userWatchlist:' . auth()->user()->id . ':masterEventUniqueId:' . $row['master_event_unique_id'], [
                        'value' => true
                    ]);
                }
            }

            if ($action == "remove") {
                $lang = "removed";

                foreach ($masterEventUniqueIds as $row) {
                    UserWatchlist::where('user_id', auth()->user()->id)
                                 ->where('master_event_id', $row['id'])
                                 ->delete();
                    app('swoole')->userWatchlistTable->del('userWatchlist:' . auth()->user()->id . ':masterEventUniqueId:' . $row['master_event_unique_id']);
                }
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'message'     => trans('game.watchlist.' . $lang)
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "TradeController",
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
     * Get Leagues per Authenticated User's default Sport
     *
     * @return json
     */
    public function getInitialLeagues()
    {
        try {
            /** Get Authenticated User's Default Initial Sport : Last Sport visited */
            $data            = getUserDefault(auth()->user()->id, 'sport');
            $dataSchedule    = [
                'inplay' => [],
                'today'  => [],
                'early'  => []
            ];
            $userProviderIds = UserProviderConfiguration::getProviderIdList(auth()->user()->id);

            foreach ($dataSchedule as $key => $sched) {
                $ActiveleaguesWithActiveEvents = MasterLeague::getSideBarLeaguesBySportAndGameSchedule($data['default_sport'], auth()->user()->id, $key);

                foreach ($ActiveleaguesWithActiveEvents as $league) {
                    $dataSchedule[$key][$league->name] = [
                        'name'        => $league->name,
                        'match_count' => $league->match_count
                    ];
                }
                $dataSchedule[$key] = array_values($dataSchedule[$key]);
            }

            if (!$data['status']) {
                $toLogs = [
                    "class"       => "TradeController",
                    "message"     => $data['error'],
                    "module"      => "API_ERROR",
                    "status_code" => 400,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

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
            $toLogs = [
                "class"       => "TradeController",
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
     * Add/Remove Authenticated User's Selected Sidebar Leagues
     *
     * @param  $request \Illuminate\Http\Request
     * @return json
     */
    public function postManageSidebarLeagues($action, ToggleLeaguesRequest $request)
    {
        try {
            $userId       = auth()->user()->id;
            $masterLeague = MasterLeague::getLeagueDetailsByName($request->league_name);
            $checkTable   = UserSelectedLeague::getUserSelectedLeague($userId, [
                'league_id' => $masterLeague->id,
                'schedule'  => $request->schedule,
                'sport_id'  => $request->sport_id
            ]);

            $swtKey = 'userId:' . $userId . ':sId:' . $request->sport_id . ':lId:' . $masterLeague->id . ':schedule:' . $request->schedule;

            if ($action == 'add' && $checkTable->count() == 0) {
                UserSelectedLeague::create(
                    [
                        'user_id'          => $userId,
                        'sport_id'         => $request->sport_id,
                        'master_league_id' => $masterLeague->id,
                        'game_schedule'    => $request->schedule
                    ]
                );

                if (empty($_SERVER['_PHPUNIT'])) {
                    if (!SwooleHandler::exists('userSelectedLeaguesTable', $swtKey)) {
                        SwooleHandler::setValue('userSelectedLeaguesTable', $swtKey, [
                            'user_id'     => $userId,
                            'sport_id'    => $request->sport_id,
                            'league_name' => $request->league_name,
                            'schedule'    => $request->schedule
                        ]);
                    }
                }
            } else if ($action == 'remove' && $checkTable->count() > 0) {
                $checkTable->delete();

                if (empty($_SERVER['_PHPUNIT'])) {
                    $previouslySelectedEvents = DB::table('master_events AS me')
                        ->join('event_groups AS eg', 'eg.master_event_id', 'me.id')
                        ->join('events AS e', 'e.id', 'eg.event_id')
                        ->where('me.master_league_id', $masterLeague->id)
                        ->where('e.game_schedule', $request->schedule)
                        ->where('me.sport_id', $request->sport_id)
                        ->get();

                    foreach ($previouslySelectedEvents as $events) {
                        $topicTable = SwooleHandler::table('topicTable');
                        foreach ($topicTable as $k => $topic) {
                            if (strpos($topic['topic_name'], 'uid-' . $events->master_event_unique_id) === 0) {
                                SwooleHandler::remove('topicTable', $k);
                            }
                        }
                    }

                    if (SwooleHandler::exists('userSelectedLeaguesTable', $swtKey)) {
                        SwooleHandler::remove('userSelectedLeaguesTable', $swtKey);
                    }
                }
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'message'     => trans('notifications.save.success')
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "TradeController",
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

    public function getUserEvents()
    {
        try {
            $watchlistData    = [];
            $userSelectedData = [];
            $type             = [
                'user_watchlist',
                'user_selected',
            ];

            $userId     = auth()->user()->id;
            $topicTable = app('swoole')->topicTable;
            $default    = getUserDefault(auth()->user()->id, 'sport');
            foreach ($type as $row) {
                if ($row == 'user_watchlist') {
                    $transformed = Game::getWatchlistEvents($userId, $default['default_sport']);
                    $watchlist   = eventTransformation($transformed, $userId, $topicTable, 'watchlist');
                    if (!empty($watchlist)) {
                        foreach ($watchlist as $key => $league) {
                            $watchlistData[$key] = array_values($watchlist[$key]);
                        }
                    }
                } else {
                    $userProviderIds = UserProviderConfiguration::getProviderIdList($userId);
                    $transformed  = Game::getSelectedLeagueEvents($userId, $default['default_sport']);
                    $userSelected = eventTransformation($transformed, $userId, $topicTable, 'selected', [], false);
                    if (!empty($userSelected)) {
                        foreach ($userSelected as $key => $schedule) {
                            foreach ($schedule as $k => $league) {
                                foreach ($league as $event) {
                                    $providersOfEvents    = Game::providersOfEventsByUid($event->master_event_unique_id, $userProviderIds, $schedule)->get();

                                    $providerIds = array_map(function($x){ return $x->id; }, $providersOfEvents->toArray());
                                    if (!in_array($primaryProviderId, $providerIds)) {
                                            continue;
                                    }
                                    $userSelectedData[$key][$k][] = $event;
                                }
                                
                            }
                        }
                    }
                }
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => [
                    'user_watchlist' => $watchlistData,
                    'user_selected'  => $userSelectedData
                ]
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "TradeController",
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

    public function getEventOtherMarkets($meUID, Request $request)
    {
        try {
            $transformed     = Game:: getOtherMarketsByMasterEventId($meUID);
            $userProviderIds = UserProviderConfiguration::getProviderIdList(auth()->user()->id);

            $data = [];
            array_map(function ($transformed) use (&$data, $userProviderIds) {
                if (!in_array($transformed->provider_id, $userProviderIds)) {
                    return $transformed;
                }
                if (!empty($transformed->odd_label)) {
                    $data[$transformed->market_event_identifier][$transformed->odd_label . $transformed->type . $transformed->market_flag] = [
                        'odds'                    => (double) $transformed->odds,
                        'market_id'               => $transformed->master_event_market_unique_id,
                        'points'                  => $transformed->odd_label,
                        'master_event_identifier' => $transformed->market_event_identifier,
                        'odd_type'                => $transformed->type,
                        'market_flag'             => $transformed->market_flag,
                        'market_event_identifier' => $transformed->market_event_identifier,
                        'provider_alias'          => $transformed->alias
                    ];
                }
            }, $transformed->toArray());

            $result      = [];
            $otherValues = [];
            foreach ($data as $masterEventIdentifier) {
                foreach ($masterEventIdentifier as $k => $d) {
                    if (empty($otherValues[$d['odd_type'] . $d['market_flag'] . $d['points']])) {
                        $result[$d['market_event_identifier']][$d['odd_type']][$d['market_flag']] = [
                            'market_id'      => $d['market_id'],
                            'odds'           => $d['odds'],
                            'points'         => $d['points'],
                            'provider_alias' => $d['provider_alias']
                        ];
                        $otherValues[$d['odd_type'] . $d['market_flag'] . $d['points']]           = $d['market_event_identifier'];
                    } else {
                        $key = $otherValues[$d['odd_type'] . $d['market_flag'] . $d['points']];
                        if (
                            !empty($result[$key][$d['odd_type']]) &&
                            $result[$key][$d['odd_type']][$d['market_flag']]['market_id'] == $d['market_id'] &&
                            $result[$key][$d['odd_type']][$d['market_flag']]['odds'] < $d['odds']
                        ) {
                            $result[$key][$d['odd_type']][$d['market_flag']]['odds'] = $d['odds'];
                        }
                    }
                }
            }

            krsort($result, SORT_NUMERIC);

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $result
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "TradeController",
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

    public function postSearchSuggestions(Request $request)
    {
        try {
            if (!$request->has('page')) {
                $toLogs = [
                    "class"       => "TradeController",
                    "message"     => trans('generic.not-found'),
                    "module"      => "API_ERROR",
                    "status_code" => 404,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'      => false,
                    'status_code' => 404,
                    'message'     => trans('generic.not-found')
                ], 404);
            }

            if ($request->page < 1) {
                $toLogs = [
                    "class"       => "TradeController",
                    "message"     => trans('generic.bad-request'),
                    "module"      => "API_ERROR",
                    "status_code" => 400,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'      => false,
                    'status_code' => 400,
                    'message'     => trans('generic.bad-request')
                ], 400);
            }

            $limit = 20;

            $data            = getUserDefault(auth()->user()->id, 'sport');
            $userProviderIds = UserProviderConfiguration::getProviderIdList(auth()->user()->id);

            $leagues = MasterLeague::getLeaguesBySportAndGameSchedule($data['default_sport'], auth()->user()->id, $userProviderIds, "", $request->keyword);
            $events  = Game::getAvailableEvents(auth()->user()->id, $request->keyword);

            $results = $leagues->union($events);

            $query = $results->limit($limit)
                             ->offset(($request->page - 1) * $limit);

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $request->keyword == "" ? [] : $query->get(),
                'paginated'   => (($request->page * $limit) - $results->count()) >= 0 ? false : true
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "TradeController",
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
