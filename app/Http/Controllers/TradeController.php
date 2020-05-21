<?php

namespace App\Http\Controllers;

use App\Models\{
    MasterEvent,
    MasterLeague,
    Sport,
    UserSelectedLeague,
    UserWatchlist
};
use Illuminate\Support\Facades\{
    DB,
    Log
};
use Illuminate\Http\Request;
use Exception;
use DateTime;
use Carbon\Carbon;

class TradeController extends Controller
{

    const SORT_EVENT_BY_LEAGUE_NAME = 1;

    /**
     * Fetch Authenticated User's Lists of Open Orders
     *
     * @return json
     */
    public function getUserBetbar()
    {
        try {
            $betBarData = DB::table('orders AS o')
                ->join('providers AS p', 'p.id', 'o.provider_id')
                ->join('master_event_markets AS mem', 'mem.master_event_market_unique_id',
                    'o.master_event_market_unique_id')
                ->join('master_events AS me', 'me.master_event_unique_id', 'mem.master_event_unique_id')
                ->join('odd_types AS ot', 'ot.id', 'mem.odd_type_id')
                ->join('sport_odd_type AS sot', 'sot.odd_type_id', 'ot.id')
                ->distinct()
                ->where('sot.sport_id', DB::raw('o.sport_id'))
                ->where('o.user_id', auth()->user()->id)
                ->where('o.settled_date', '=', '')
                ->orWhereNull('o.settled_date')
                ->select([
                    'o.id AS order_id',
                    'p.alias',
                    'o.master_event_market_unique_id',
                    'me.master_event_unique_id',
                    'me.master_league_name',
                    'me.master_home_team_name',
                    'me.master_away_team_name',
                    'me.score',
                    'me.game_schedule',
                    'mem.market_flag',
                    'ot.id AS odd_type_id',
                    'sot.name',
                    'o.odds',
                    'o.stake',
                    'o.status',
                    'o.created_at',
                    'o.order_expiry',
                    'o.odd_label'
                ])
                ->orderBy('o.created_at', 'desc')
                ->get();

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
                    $score  = explode(" - ", $betData->score);

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
                        'created_at'     => $betData->created_at
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
                    app('swoole')->userWatchlistTable->set('userWatchlist:' . auth()->user()->id . ':masterEventUniqueId:' . $row['master_event_unique_id'],
                        ['value' => true]);
                }
            }

            if ($action == "remove") {
                $lang = "removed";

                foreach ($masterEventUniqueIds AS $row) {
                    UserWatchlist::where('user_id', auth()->user()->id)
                        ->where('master_event_unique_id', $row['master_event_unique_id'])
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
            $data = getUserDefault(auth()->user()->id, 'sport');


            $dataSchedule = [
                'inplay' => [],
                'today'  => [],
                'early'  => []
            ];
            foreach ($dataSchedule as $key => $sched) {
                $leaguesQuery = DB::table('master_leagues')
                    ->join('master_events', 'master_events.master_league_name', 'master_leagues.master_league_name')
                    ->join('master_event_links', 'master_event_links.master_event_unique_id', 'master_events.master_event_unique_id')
                    ->join('events', 'events.id', 'master_event_links.event_id')
                    ->where('master_leagues.sport_id', $data['default_sport'])
                    ->whereNull('master_leagues.deleted_at')
                    ->whereNull('master_events.deleted_at')
                    ->whereNull('events.deleted_at')
                    ->where('master_events.game_schedule', $key)
                    ->groupBy('master_leagues.master_league_name')
                    ->select('master_leagues.master_league_name',
                        DB::raw('COUNT(master_leagues.master_league_name) as match_count'))
                    ->distinct()
                    ->get();

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
    public function postManageSidebarLeagues(Request $request)
    {
        try {
            $checkTable = UserSelectedLeague::where('user_id', auth()->user()->id)
                ->where('master_league_name', $request->league_name)
                ->where('game_schedule', $request->schedule)
                ->where('sport_id', $request->sport_id);

            $userSelectedLeagueTable = app('swoole')->userSelectedLeaguesTable;
            if (Sport::find($request->sport_id)) {
                $userId = auth()->user()->id;
                $swtKey = 'userId:' . $userId . ':sId:' . $request->sport_id . ':schedule:' . $request->schedule . ':uniqueId:' . uniqid();
                if ($checkTable->count() == 0) {
                    if (MasterLeague::where('master_league_name', $request->league_name)->count() != 0) {
                        UserSelectedLeague::create(
                            [
                                'user_id'            => $userId,
                                'master_league_name' => $request->league_name,
                                'game_schedule'      => $request->schedule,
                                'sport_id'           => $request->sport_id
                            ]
                        );
                        if (empty($_SERVER['_PHPUNIT'])) {
                            $isSelectedLeagueFoundInSWT = false;
                            foreach ($userSelectedLeagueTable as $key => $row) {
                                if (strpos($key,
                                    'userId:' . $userId . ':sId:' . $request->sport_id . ':schedule:' . $request->schedule) === 0) {
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
                    } else if (empty($_SERVER['_PHPUNIT'])) {
                        foreach ($userSelectedLeagueTable as $key => $row) {
                            if (strpos($key,
                                    'userId:' . $userId . ':sId:' . $request->sport_id . ':schedule:' . $request->schedule) === 0) {
                                if ($row['league_name'] == $request->league_name && $row['schedule'] == $request->schedule && $row['sport_id'] == $request->sport_id) {
                                    $userSelectedLeagueTable->del($key);
                                    break;
                                }
                            }
                        }

                        return response()->json([
                            'status'      => true,
                            'status_code' => 405,
                            'message'     => trans('generic.method-not-allowed')
                        ], 405);
                    }
                } else {
                    $checkTable->delete();

                    if (empty($_SERVER['_PHPUNIT'])) {
                        $topicTable        = app('swoole')->topicTable;
                        $eventsTable       = app('swoole')->eventsTable;
                        $eventMarketsTable = app('swoole')->eventMarketsTable;

                        foreach ($eventsTable as $eKey => $event) {
                            if ($event['master_league_name'] == $request->league_name && $event['game_schedule'] == $request->schedule) {

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
                            if (strpos($key,
                                    'userId:' . $userId . ':sId:' . $request->sport_id . ':schedule:' . $request->schedule) === 0) {
                                if ($row['league_name'] == $request->league_name && $row['schedule'] == $request->schedule && $row['sport_id'] == $request->sport_id) {
                                    $userSelectedLeagueTable->del($key);
                                    break;
                                }
                            }
                        }
                    }
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

            foreach ($type AS $row) {
                $transformed = DB::table('master_leagues as ml')
                    ->join('sports as s', 's.id', 'ml.sport_id')
                    ->join('master_events as me', 'me.master_league_name', 'ml.master_league_name')
                    ->join('master_event_markets as mem', 'mem.master_event_unique_id', 'me.master_event_unique_id')
                    ->join('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                    ->join('master_event_market_links as meml', 'meml.master_event_market_unique_id',
                        'mem.master_event_market_unique_id')
                    ->join('event_markets as em', 'em.id', 'meml.event_market_id')
                    ->whereNull('me.deleted_at')
                    ->whereNull('ml.deleted_at');

                if ($row == 'user_watchlist') {
                    $transformed = $transformed->join('user_watchlist AS uw', 'me.master_event_unique_id', '=',
                        'uw.master_event_unique_id')
                        ->where('uw.user_id', auth()->user()->id);
                } else {
                    $transformed = $transformed->join('user_selected_leagues AS sl', 'ml.master_league_name', '=',
                        'sl.master_league_name')
                        ->where('sl.game_schedule', DB::raw('me.game_schedule'))
                        ->where('sl.game_schedule', DB::raw('em.game_schedule'))
                        ->where('sl.user_id', auth()->user()->id);
                }

                $transformed = $transformed->whereNull('me.deleted_at')
                    ->where('mem.is_main', true)
                    ->select('ml.sport_id', 'ml.master_league_name', 's.sport',
                        'me.master_event_unique_id', 'me.master_home_team_name', 'me.master_away_team_name',
                        'me.ref_schedule', 'me.game_schedule', 'me.score', 'me.running_time',
                        'me.home_penalty', 'me.away_penalty', 'mem.odd_type_id', 'mem.master_event_market_unique_id',
                        'mem.is_main', 'mem.market_flag',
                        'ot.type', 'em.odds', 'em.odd_label', 'em.provider_id', 
                        DB::raw('(SELECT count(*) FROM orders as internalOrder WHERE internalOrder.market_id = em.bet_identifier AND internalOrder.user_id = ' . auth()->user()->id . ') as bet_count'))
                    ->distinct()->get();

                $userConfig = getUserDefault(auth()->user()->id, 'sort-event')['default_sort'];

                if ($row == 'user_watchlist') {
                    array_map(function ($transformed) use (&$watchlist, $userConfig) {
                        if ($userConfig == self::SORT_EVENT_BY_LEAGUE_NAME) {
                            $groupIndex = $transformed->master_league_name;
                        } else {
                            $refSchedule = DateTime::createFromFormat('Y-m-d H:i:s', $transformed->ref_schedule);
                            $groupIndex  = $refSchedule->format('[H:i:s]') . ' ' . $transformed->master_league_name;
                        }

                        if (empty($watchlist[$groupIndex][$transformed->master_event_unique_id])) {
                            $watchlist[$groupIndex][$transformed->master_event_unique_id] = [
                                "uid"           => $transformed->master_event_unique_id,
                                'sport_id'      => $transformed->sport_id,
                                'sport'         => $transformed->sport,
                                'provider_id'   => $transformed->provider_id,
                                'game_schedule' => $transformed->game_schedule,
                                'league_name'   => $transformed->master_league_name,
                                'running_time'  => $transformed->running_time,
                                'ref_schedule'  => $transformed->ref_schedule,
                                'has_bet'        => $transformed->bet_count > 0 ? true : false
                            ];
                        }
                        if (empty($watchlist[$groupIndex][$transformed->master_event_unique_id]['home'])) {
                            $watchlist[$groupIndex][$transformed->master_event_unique_id]['home'] = [
                                'name'    => $transformed->master_home_team_name,
                                'score'   => empty($transformed->score) ? '' : array_values(explode(' - ',
                                    $transformed->score))[0],
                                'redcard' => $transformed->home_penalty
                            ];
                        }
                        if (empty($watchlist[$groupIndex][$transformed->master_event_unique_id]['away'])) {
                            $watchlist[$groupIndex][$transformed->master_event_unique_id]['away'] = [
                                'name'    => $transformed->master_away_team_name,
                                'score'   => empty($transformed->score) ? '' : array_values(explode(' - ',
                                    $transformed->score))[1],
                                'redcard' => $transformed->home_penalty
                            ];
                        }
                        if (empty($watchlist[$groupIndex][$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag])) {
                            $watchlist[$groupIndex][$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag] = [
                                'odds'      => (double)$transformed->odds,
                                'market_id' => $transformed->master_event_market_unique_id
                            ];
                            if (!empty($transformed->odd_label)) {
                                $watchlist[$groupIndex][$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['points'] = $transformed->odd_label;
                            }
                        }
                    }, $transformed->toArray());
                    foreach ($watchlist as $key => $league) {
                        $watchlistData[$key] = array_values($watchlist[$key]);
                    }
                } else {
                    $topicTable = app('swoole')->topicTable;
                    array_map(function ($transformed) use (&$userSelected, $row, $userConfig, $topicTable) {
                        if ($userConfig == self::SORT_EVENT_BY_LEAGUE_NAME) {
                            $groupIndex = $transformed->master_league_name;
                        } else {
                            $refSchedule = DateTime::createFromFormat('Y-m-d H:i:s', $transformed->ref_schedule);
                            $groupIndex  = $refSchedule->format('[H:i:s]') . ' ' . $transformed->master_league_name;
                        }
                        if (empty($userSelected[$transformed->game_schedule][$groupIndex][$transformed->master_event_unique_id])) {
                            $userSelected[$transformed->game_schedule][$groupIndex][$transformed->master_event_unique_id] = [
                                "uid"           => $transformed->master_event_unique_id,
                                'sport_id'      => $transformed->sport_id,
                                'sport'         => $transformed->sport,
                                'provider_id'   => $transformed->provider_id,
                                'game_schedule' => $transformed->game_schedule,
                                'league_name'   => $transformed->master_league_name,
                                'running_time'  => $transformed->running_time,
                                'ref_schedule'  => $transformed->ref_schedule,
                                'has_bet'        => $transformed->bet_count > 0 ? true : false
                            ];
                        }
                        if (empty($userSelected[$transformed->game_schedule][$groupIndex][$transformed->master_event_unique_id]['home'])) {
                            $userSelected[$transformed->game_schedule][$groupIndex][$transformed->master_event_unique_id]['home'] = [
                                'name'    => $transformed->master_home_team_name,
                                'score'   => empty($transformed->score) ? '' : array_values(explode(' - ',
                                    $transformed->score))[0],
                                'redcard' => $transformed->home_penalty
                            ];
                        }
                        if (empty($userSelected[$transformed->game_schedule][$groupIndex][$transformed->master_event_unique_id]['away'])) {
                            $userSelected[$transformed->game_schedule][$groupIndex][$transformed->master_event_unique_id]['away'] = [
                                'name'    => $transformed->master_away_team_name,
                                'score'   => empty($transformed->score) ? '' : array_values(explode(' - ',
                                    $transformed->score))[1],
                                'redcard' => $transformed->home_penalty
                            ];
                        }
                        if (empty($userSelected[$transformed->game_schedule][$groupIndex][$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag])) {
                            $userSelected[$transformed->game_schedule][$groupIndex][$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag] = [
                                'odds'      => (double)$transformed->odds,
                                'market_id' => $transformed->master_event_market_unique_id
                            ];

                            if (!empty($transformed->odd_label)) {
                                $userSelected[$transformed->game_schedule][$groupIndex][$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['points'] = $transformed->odd_label;
                            }

                            if (empty($_SERVER['_PHPUNIT'])) {
                                $notFound = true;
                                foreach ($topicTable as $key => $row) {
                                    if ($row['topic_name'] == 'market-id-' . $transformed->master_event_market_unique_id) {
                                        $notFound = false;
                                        break;
                                    }
                                }

                                if ($notFound) {
                                    $topicTable->set('userId:' . auth()->user()->id . ':unique:' . uniqid(), [
                                        'user_id'    => auth()->user()->id,
                                        'topic_name' => 'market-id-' . $transformed->master_event_market_unique_id
                                    ]);
                                }
                            }
                        }
                    }, $transformed->toArray());
                    foreach ($userSelected as $key => $schedule) {
                        foreach ($schedule as $k => $league) {
                            $userSelectedData[$key][$k] = array_values($userSelected[$key][$k]);
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

    public function getEventOtherMarkets($memUID, Request $request)
    {
        try {
            $transformed = DB::table('master_events as me')
                ->join('sports as s', 's.id', 'me.sport_id')
                ->join('master_event_markets as mem', 'mem.master_event_unique_id', 'me.master_event_unique_id')
                ->join('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                ->join('master_event_market_links as meml', 'meml.master_event_market_unique_id',
                    'mem.master_event_market_unique_id')
                ->join('event_markets as em', 'em.id', 'meml.event_market_id')
                ->whereNull('me.deleted_at')
                ->where('mem.is_main', false)
                ->where('me.master_event_unique_id', $memUID)
                ->where('me.game_schedule', DB::raw('em.game_schedule'))
                ->select('s.sport',
                    'me.master_event_unique_id', 'me.master_home_team_name', 'me.master_away_team_name',
                    'me.ref_schedule', 'me.game_schedule', 'me.score', 'me.running_time',
                    'me.home_penalty', 'me.away_penalty', 'mem.odd_type_id', 'mem.master_event_market_unique_id',
                    'mem.is_main', 'mem.market_flag',
                    'ot.type', 'em.odds', 'em.odd_label', 'em.provider_id', 'em.event_identifier')
                ->distinct()->get();

            $data = [];
            array_map(function ($transformed) use (&$data) {
                if (!empty($transformed->odd_label)) {
                    if (empty($data[$transformed->event_identifier][$transformed->type][$transformed->market_flag])) {
                        $data[$transformed->event_identifier][$transformed->type][$transformed->market_flag] = [
                            'odds'      => (double)$transformed->odds,
                            'market_id' => $transformed->master_event_market_unique_id,
                            'points'    => $transformed->odd_label
                        ];
                    }
                }
            }, $transformed->toArray());

            krsort($data, SORT_NUMERIC);
            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $data
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
            $data  = DB::table('search_suggestions')
                ->where('label', 'ILIKE', '%' . trim($request->keyword) . '%');
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
