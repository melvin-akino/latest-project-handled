<?php

namespace App\Http\Controllers;

use App\Models\{
    Game,
    Order,
    MasterEvent,
    MasterLeague,
    Sport,
    UserSelectedLeague,
    UserWatchlist,
    UserConfiguration,
    Timezones,
    Provider,
    UserProviderConfiguration
};
use Illuminate\Support\Facades\{
    DB,
    Log
};
use Illuminate\Http\Request;
use Exception;
use DateTime;
use Carbon\Carbon;

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

            $data = [];
            foreach ($betBarData as $betData) {
                $proceed = false;
                if ($betData->status == 'SUCCESS') {
                    $proceed = true;
                } else if ($betData->status == 'PENDING') {
                    $currentTime = Carbon::now()->toDateTimeString();
                    $expireTime  = Carbon::parse($betData->created_at)->addSeconds($betData->order_expiry)->toDateTimeString();
                    if ($currentTime <= $expireTime) {
                        $proceed = true;
                    } else {
                        $proceed = false;
                    }
                }

                if ($proceed) {
                    $score = explode(" - ", $betData->score);

                    $data[] = [
                        'order_id'       => $betData->order_id,
                        'provider_alias' => $betData->alias,
                        'event_id'       => $betData->master_event_unique_id,
                        'market_id'      => $betData->master_event_market_unique_id,
                        'odd_type_id'    => $betData->odd_type_id,
                        'league_name'    => $betData->master_league_name,
                        'game_schedule'  => $betData->game_schedule,
                        'home'           => $betData->master_home_team_name,
                        'away'           => $betData->master_away_team_name,
                        'bet_info'       => [
                            $betData->market_flag,
                            $betData->name,
                            $betData->odds,
                            $betData->stake,
                            $betData->odd_label,
                            $betData->market_flag == 'HOME' ? $betData->master_home_team_name : $betData->master_away_team_name
                        ],
                        'score'          => $betData->score,
                        'home_score'     => $score[0],
                        'away_score'     => $score[1],
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
            Log::error($e->getMessage());
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
                        $masterEventUniqueIds = MasterEvent::getActiveEvents('master_league_id', '=', $leagueId)
                                                           ->get(['id', 'master_event_unique_id'])
                                                           ->toArray();
                    } else {
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
                $userEvents = app('swoole')->userEventsTable;

                foreach ($masterEventUniqueIds as $row) {
                    UserWatchlist::where('user_id', auth()->user()->id)
                                 ->where('master_event_id', $row['id'])
                                 ->delete();
                    app('swoole')->userWatchlistTable->del('userWatchlist:' . auth()->user()->id . ':masterEventUniqueId:' . $row['master_event_unique_id']);

                    foreach ($userEvents as $key => $data) {
                        $swtKeyArray = explode(':o', $key);
                        if ($swtKeyArray[0] == 'watchlist:u:' . auth()->user()->id . ':e:' . $row['id']) {
                            $userEvents->del($key);
                        } else {
                            continue;
                        }
                    }

                    $gameDetails = Game::getGameDetailsByMeId($row['id']);

                    foreach($gameDetails as $data) {
                        $swtKey = 'selected:u:' . auth()->user()->id . ':l:' . $data->league_id . ':s:' . $data->game_schedule . ':e:' . $data->master_event_id  .  ':o:' . $data->odd_type_id . ':t:' . $data->market_flag;
                        foreach($data as $key => $value) {
                            $userEvents->set($swtKey, [$key => $value]);
                        }
                    }
                }
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'message'     => trans('game.watchlist.' . $lang)
            ], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
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
            $data         = getUserDefault(auth()->user()->id, 'sport');
            $dataSchedule = [
                'inplay' => [],
                'today'  => [],
                'early'  => []
            ];
            $userProviderIds = UserProviderConfiguration::getProviderIdList(auth()->user()->id);

            foreach ($dataSchedule as $key => $sched) {
                $leaguesQuery = MasterLeague::getLeaguesBySportAndGameShedule($data['default_sport'], $userProviderIds, $key);

                foreach ($leaguesQuery as $league) {
                    $dataSchedule[$key][$league->master_league_name] = [
                        'name'        => $league->master_league_name,
                        'match_count' => $league->match_count
                    ];
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
            Log::error($e->getMessage());
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
            $masterLeague = MasterLeague::where('name', $request->league_name)->first();
            $checkTable = UserSelectedLeague::where('user_id', auth()->user()->id)
                                            ->where('master_league_id', $masterLeague->id)
                                            ->where('game_schedule', $request->schedule)
                                            ->where('sport_id', $request->sport_id);

            $userSelectedLeagueTable = app('swoole')->userSelectedLeaguesTable;

            $userId = auth()->user()->id;
            $swtKey = 'userId:' . $userId . ':sId:' . $request->sport_id . ':schedule:' . $request->schedule . ':uniqueId:' . uniqid();

            if ($action == 'add' && $checkTable->count() == 0) {
                UserSelectedLeague::create(
                    [
                        'user_id'          => $userId,
                        'master_league_id' => $masterLeague->id,
                        'game_schedule'    => $request->schedule,
                        'sport_id'         => $request->sport_id
                    ]
                );

                if (empty($_SERVER['_PHPUNIT'])) {
                    $isSelectedLeagueFoundInSWT = false;

                    foreach ($userSelectedLeagueTable as $key => $row) {
                        if (strpos($key, 'userId:' . $userId . ':sId:' . $request->sport_id . ':schedule:' . $request->schedule) === 0) {
                            if ($row['league_name'] == $request->league_name && $row['schedule'] == $request->schedule && $row['sport_id'] == $request->sport_id) {
                                $isSelectedLeagueFoundInSWT = true;

                                break;
                            }
                        }
                    }

                    if (!$isSelectedLeagueFoundInSWT) {
                        $userSelectedLeagueTable->set($swtKey, [
                            'user_id'     => $userId,
                            'schedule'    => $request->schedule,
                            'league_name' => $request->league_name,
                            'sport_id'    => $request->sport_id
                        ]);
                    }
                }
            } else if($action == 'remove' && $checkTable->count() > 0) {
                $checkTable->delete();

                if (empty($_SERVER['_PHPUNIT'])) {
                    $topicTable        = app('swoole')->topicTable;
                    $eventsTable       = app('swoole')->eventsTable;
                    $eventMarketsTable = app('swoole')->eventMarketsTable;
                    $userEvents        = app('swoole')->userEventsTable;

                    foreach ($eventsTable as $eKey => $event) {
                        if ($event['master_league_id'] == $masterLeague->id && $event['game_schedule'] == $request->schedule) {
                            foreach ($eventMarketsTable as $eMKey => $eventMarket) {
                                if ($eventMarket['master_event_unique_id'] == $event['master_event_unique_id']) {
                                    foreach ($topicTable as $k => $topic) {
                                        if ($topic['user_id'] == auth()->user()->id && $topic['topic_name'] == 'market-id-' . $eventMarket['master_event_market_unique_id']) {
                                            $topicTable->del($k);

                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    foreach ($userSelectedLeagueTable as $key => $row) {
                        if (strpos($key, 'userId:' . $userId . ':sId:' . $request->sport_id . ':schedule:' . $request->schedule) === 0) {
                            if ($row['league_name'] == $request->league_name && $row['schedule'] == $request->schedule && $row['sport_id'] == $request->sport_id) {
                                $userSelectedLeagueTable->del($key);

                                break;
                            }
                        }
                    }

                    foreach ($userEvents as $key => $row) {
                        if (strpos($key, 'selected:u:' . $userId . ':l:' . $masterLeague->id . ':s:' . $request->schedule) !== false) {
                            if ($row['master_league_name'] == $request->league_name && $row['game_schedule'] == $request->schedule) {
                                $userEvents->del($key);
                            }
                        } else {
                            continue;
                        }
                    }
                }
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'message'     => trans('notifications.save.success')
            ], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
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
            $watchlist        = [];
            $userSelectedData = [];
            $userSelected     = [];
            $type             = [
                'user_watchlist',
                'user_selected',
            ];
            $userId           = auth()->user()->id;
            $providerId = Provider::getMostPriorityProvider(auth()->user()->id);
            $userEvents = app('swoole')->userEventsTable;
            if ($providerId) {
                foreach ($type as $row) {
                    if ($row == 'user_watchlist') {
                        if (!checkIfInSWTKey($userEvents, 'watchlist:u:' . $userId)) {
                            $transformed = Game::getWatchlistEvents($userId);
                            foreach($transformed as $data) {
                                $swtKey = 'watchlist:u:' . $userId . ':e:' . $data->master_event_id . ':o:' . $data->odd_type_id . ':t:' . $data->market_flag;
                                foreach($data as $key => $value) {
                                    $userEvents->set($swtKey, [$key => $value]);
                                }
                            }
                            Log::info('Watchlist events from query');
                        } else {
                            $transformed = getFromSWT($userEvents, 'watchlist:u:' . $userId);
                            Log::info('Watchlist events from SWT');
                        }
                    } else {
                        if (!checkIfInSWTKey($userEvents, 'selected:u:' . $userId)) {
                            $transformed = Game::getSelectedLeagueEvents($userId);
                            foreach($transformed as $data) {
                                $swtKey = 'selected:u:' . $userId . ':l:' . $data->league_id . ':s:' . $data->game_schedule . ':e:' . $data->master_event_id  .  ':o:' . $data->odd_type_id . ':t:' . $data->market_flag;
                                foreach($data as $key => $value) {
                                    $userEvents->set($swtKey, [$key => $value]);
                                }
                            }
                            Log::info('Selected events from query');
                        } else {
                            $transformed = getFromSWT($userEvents, 'selected:u:' . $userId);
                            Log::info('Selected events from SWT');
                        }
                    }

                    /** TO DO: Adjust getUserDefault */
                    $userConfig    = getUserDefault($userId, 'sort-event')['default_sort'];
                    $userTz        = "Etc/UTC";
                    $getUserConfig = UserConfiguration::getUserConfig($userId)
                                                      ->where('type', 'timezone')
                                                      ->first();

                    if ($getUserConfig) {
                        $userTz = Timezones::find($getUserConfig->value)->name;
                    }

                    $userProviderIds = UserProviderConfiguration::getProviderIdList($userId);
                    $topicTable      = app('swoole')->topicTable;
                    if ($row == 'user_watchlist') {
                        $watchlist = eventTransformation($transformed, $userConfig, $userTz, $userId, $userProviderIds, $topicTable, 'watchlist');
                        foreach ($watchlist as $key => $league) {
                            $watchlistData[$key] = array_values($watchlist[$key]);
                        }
                    } else {
                        $userSelected = eventTransformation($transformed, $userConfig, $userTz, $userId, $userProviderIds, $topicTable, 'selected');
                        foreach ($userSelected as $key => $schedule) {
                            foreach ($schedule as $k => $league) {
                                $userSelectedData[$key][$k] = array_values($userSelected[$key][$k]);
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
            Log::error($e->getMessage());
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
            $transformed     = Game:: getOtherMarketsByMemUID($meUID);
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
            Log::error($e->getMessage());
            Log::error($e->getLine());
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
                return response()->json([
                    'status'      => false,
                    'status_code' => 404,
                    'message'     => trans('generic.not-found')
                ], 404);
            }

            if ($request->page < 1) {
                return response()->json([
                    'status'      => false,
                    'status_code' => 400,
                    'message'     => trans('generic.bad-request')
                ], 400);
            }

            $limit = 20;
            $data  = Game::searchSuggestion($request->keyword);
            $query = $data->limit($limit)
                          ->offset(($request->page - 1) * $limit);

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $request->keyword == "" ? [] : $query->get(),
                'paginated'   => (($request->page * $limit) - $data->count()) >= 0 ? false : true
            ], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }
}
