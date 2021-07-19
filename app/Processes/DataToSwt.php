<?php

namespace App\Processes;

use App\Facades\SwooleHandler;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\{Order, Provider, Sport, SystemConfiguration};
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Swoole\Http\Server;
use Swoole\Process;

class DataToSwt implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        // DB to SWT Initialization
        $swooleProcesses = [
            'Sports',
            'OddTypes',
            'Providers',
            'Leagues',
            'Teams',
            'SportOddTypes',
            'EventRecords',
            'UserWatchlist',
            'UserProviderConfig',
            'ActiveEvents',
            'UserSelectedLeagues',
            'Orders',
            'ExchangeRates',
            'Currencies',
            'UserInfo',
            'MLBetId',
            'Maintenance'
        ];

        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        $eventsRelated = [
            'Events',
            'ActiveEvents',
        ];

        foreach ($swooleProcesses as $process) {
            $method = "db2Swt" . $process;

            if (in_array($process, $eventsRelated)) {
                self::{$method}($swoole, $maxMissingCount);
            } else {
                self::{$method}($swoole);
            }
        }

        $swoole->data2SwtTable->set('data2Swt', ['value' => 1]);

        $processCount = 0;
        while (!self::$quit) {
            usleep(1000000);

            if ($processCount % (60) == 0) {
                self::db2SwtLeagues($swoole);
                self::db2SwtTeams($swoole);
                self::db2SwtEventRecords($swoole);
            }

            $processCount++;
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }

    private static function db2SwtSports(Server $swoole)
    {
        $sports      = Sport::getActiveSports()->get();
        $sportsTable = $swoole->sportsTable;

        array_map(function ($sport) use ($sportsTable) {
            $sportsTable->set('sId:' . $sport['id'], ['sport' => $sport['sport'], 'id' => $sport['id']]);
        }, $sports->toArray());
    }

    private static function db2SwtOddTypes(Server $swoole)
    {
        $oddTypes      = DB::table('odd_types')->get();
        $oddTypesTable = $swoole->oddTypesTable;

        array_map(function ($oddType) use ($oddTypesTable) {
            $oddTypesTable->set('oddType:' . $oddType->type, ['id' => $oddType->id, 'type' => $oddType->type]);
        }, $oddTypes->toArray());
    }

    private static function db2SwtProviders(Server $swoole)
    {
        $providers      = DB::table('providers')->get();
        $providersTable = $swoole->providersTable;

        array_map(function ($provider) use ($providersTable) {
            $providersTable->set('providerAlias:' . strtolower($provider->alias), [
                'id'                => $provider->id,
                'alias'             => $provider->alias,
                'is_enabled'        => $provider->is_enabled,
                'currency_id'       => $provider->currency_id,
                'punter_percentage' => $provider->punter_percentage
            ]);
        }, $providers->toArray());
    }

    private static function db2SwtLeagues(Server $swoole)
    {
        $leaguesTable = $swoole->leaguesTable;
        $leagues      = DB::table('master_leagues as ml')
            ->join('league_groups AS lg', 'lg.master_league_id', 'ml.id')
            ->join('leagues as l', 'l.id', 'lg.league_id')
            ->whereNull('ml.deleted_at')
            ->select([
                'ml.id',
                'ml.sport_id',
                'ml.name as master_league_name',
                'l.name as league_name',
                'l.provider_id',
                'ml.updated_at',
                'l.id as raw_id'
            ])
            ->get();

        array_map(function ($league) use ($leaguesTable) {
            $leaguesTable->set('sId:' . $league->sport_id . ':pId:' . $league->provider_id . ':id:' . $league->id, [
                'id'                 => $league->id,
                'sport_id'           => $league->sport_id,
                'provider_id'        => $league->provider_id,
                'master_league_name' => $league->master_league_name,
                'league_name'        => $league->league_name,
                'raw_id'             => $league->raw_id
            ]);
        }, $leagues->toArray());
    }

    private static function db2SwtTeams(Server $swoole)
    {
        $teamsTable = $swoole->teamsTable;
        $teams      = DB::table('master_teams as mt')
            ->join('team_groups AS tg', 'tg.master_team_id', 'mt.id')
            ->join('teams as t', 't.id', 'tg.team_id')
            ->select([
                'mt.id',
                't.name as team_name',
                'mt.name as master_team_name',
                't.provider_id',
                't.id as raw_id'
            ])
            ->get();

        array_map(function ($team) use ($teamsTable) {
            $teamsTable->set('pId:' . $team->provider_id . ':id:' . $team->id, [
                'id'               => $team->id,
                'team_name'        => $team->team_name,
                'master_team_name' => $team->master_team_name,
                'provider_id'      => $team->provider_id,
                'raw_id'           => $team->raw_id
            ]);
        }, $teams->toArray());
    }

    private static function db2SwtSportOddTypes(Server $swoole)
    {
        $sportOddTypesTable = $swoole->sportOddTypesTable;
        $sportOddTypes      = DB::table('sport_odd_type as sot')
            ->join('odd_types as ot', 'ot.id', 'sot.odd_type_id')
            ->join('sports as s', 's.id', 'sot.sport_id')
            ->where('s.is_enabled', true)
            ->select([
                'sot.sport_id',
                'sot.odd_type_id',
                'ot.type',
                'sot.id'
            ])
            ->get();

        array_map(function ($sportOddType) use ($sportOddTypesTable) {
            $sportOddTypesTable->set('sId:' . $sportOddType->sport_id . ':oddType:' . Str::slug($sportOddType->type), [
                'id'                => $sportOddType->id,
                'sportId'           => $sportOddType->sport_id,
                'sport_odd_type_id' => $sportOddType->id,
                'type'              => $sportOddType->type
            ]);
        }, $sportOddTypes->toArray());
    }

    private static function db2SwtEventRecords(Server $swoole)
    {
        $eventRecords = DB::table('trade_window')
            ->orderBy('sport_id')
            ->orderBy('provider_id')
            ->orderBy('event_identifier')
            ->orderBy('is_main', 'DESC')
            ->get();

        $events = [];
        foreach ($eventRecords as $eventRecord) {
            $scores = explode('-', $eventRecord->score);

            if (empty($events[$eventRecord->sport_id][$eventRecord->provider_id][$eventRecord->event_identifier])) {
                $events[$eventRecord->sport_id][$eventRecord->provider_id][$eventRecord->event_identifier] = [
                    'league_id'         => $eventRecord->league_id,
                    'team_home_id'      => $eventRecord->team_home_id,
                    'team_away_id'      => $eventRecord->team_away_id,
                    'provider'          => strtolower($eventRecord->alias),
                    'sport'             => $eventRecord->sport_id,
                    'id'                => null,
                    'home_score'        => (int) trim($scores[0]),
                    'away_score'        => (int) trim($scores[1]),
                    'home_redcard'      => $eventRecord->home_penalty,
                    'away_redcard'      => $eventRecord->away_penalty,
                    'schedule'          => $eventRecord->game_schedule,
                    'leagueName'        => $eventRecord->master_league_name,
                    'homeTeam'          => $eventRecord->master_team_home_name,
                    'awayTeam'          => $eventRecord->master_team_away_name,
                    'referenceSchedule' => Carbon::createFromFormat("Y-m-d H:i:s", $eventRecord->ref_schedule)->format("Y-m-d\TH:i:s.v"),
                    'runningtime'       => null
                ];
            }

            $eventIndex = $eventRecord->is_main ? 0 : $eventRecord->market_event_identifier;
            $events[$eventRecord->sport_id][$eventRecord->provider_id][$eventRecord->event_identifier]['events'][$eventIndex]['eventId'] = (string) $eventRecord->market_event_identifier;
            $events[$eventRecord->sport_id][$eventRecord->provider_id][$eventRecord->event_identifier]['events'][$eventIndex]['market_type'] = $eventRecord->is_main ? 1 : 2;
            $events[$eventRecord->sport_id][$eventRecord->provider_id][$eventRecord->event_identifier]['events'][$eventIndex]['market_odds'][$eventRecord->odd_type_id]['oddsType'] = $eventRecord->type;

            $marketSelection = [
                'market_id' => $eventRecord->bet_identifier,
                'indicator' => ucfirst(strtolower($eventRecord->market_flag)),
                'odds'      => $eventRecord->odds
            ];

            if (!empty($eventRecord->odd_label)) {
                $marketSelection['points'] = $eventRecord->odd_label;
            }

            SwooleHandler::setValue('oddRecordsTable', 'sId:' . $eventRecord->sport_id . ':pId:' . $eventRecord->provider_id . ':marketId:' . $eventRecord->bet_identifier, [
                'market_id'   => $eventRecord->bet_identifier,
                'sport_id'    => $eventRecord->sport_id,
                'provider_id' => $eventRecord->provider_id,
                'odds'        => $eventRecord->odds,
                'memUID'      => $eventRecord->master_event_market_unique_id
            ]);

            $events[$eventRecord->sport_id][$eventRecord->provider_id][$eventRecord->event_identifier]['events'][$eventIndex]['market_odds'][$eventRecord->odd_type_id]['marketSelection'][] = $marketSelection;
        }

        foreach ($events as $sKey => $sports) {
            foreach ($sports as $pKey => $providers) {
                foreach ($providers as $eKey => $eventIdentifier) {
                    $eventArray = [];

                    foreach ($eventIdentifier['events'] as $eventKey => $event) {
                        $marketOddsArray = [];

                        foreach ($event['market_odds'] as $marketOdds) {
                            $marketOddsArray[] = $marketOdds;
                        }

                        if ($event['market_type'] == 2) {
                            $marketeSelectionEmpty = [
                                [
                                    'market_id' => '',
                                    'indicator' => 'Home',
                                    'odds'      => ''
                                ], [
                                    'market_id' => '',
                                    'indicator' => 'Away',
                                    'odds'      => ''
                                ], [
                                    'market_id' => '',
                                    'indicator' => 'Draw',
                                    'odds'      => ''
                                ]
                            ];

                            $marketOddsArray[] = [
                                'oddsType'        => '1X2',
                                'marketSelection' => $marketeSelectionEmpty
                            ];

                            $marketOddsArray[] = [
                                'oddsType'        => 'HT 1X2',
                                'marketSelection' => $marketeSelectionEmpty
                            ];
                        }

                        $event['market_odds'] = $marketOddsArray;
                        $eventArray[]         = $event;
                    }

                    $eventIdentifier['events'] = $eventArray;

                    $eventSwtId = implode(':', [
                        "sId:" . $sKey,
                        "pId:" . $pKey,
                        "eventIdentifier:" . $eventIdentifier['events'][0]['eventId']
                    ]);

                    $data = $eventIdentifier;

                    unset($data['league_id'], $data['team_home_id'], $data['team_away_id']);

                    SwooleHandler::setValue('eventRecordsTable', $eventSwtId, [
                        'event_identifier' => $eventIdentifier['events'][0]['eventId'],
                        'sport_id'         => $sKey,
                        'league_id'        => $eventIdentifier['league_id'],
                        'team_home_id'     => $eventIdentifier['team_home_id'],
                        'team_away_id'     => $eventIdentifier['team_away_id'],
                        'ref_schedule'     => date("Y-m-d H:i:s", strtotime($eventIdentifier['referenceSchedule'])),
                        'provider_id'      => $pKey,
                        'missing_count'    => $eventRecord->missing_count,
                        'raw_data'         => json_encode($data)
                    ]);
                }
            }
        }
    }

    private static function db2SwtUserWatchlist(Server $swoole)
    {
        $userWatchlistTable = $swoole->userWatchlistTable;
        $userWatchlist      = DB::table('user_watchlist')->get();

        array_map(function ($watchlist) use ($userWatchlistTable) {
            $userWatchlistTable->set('userWatchlist:' . $watchlist->user_id . ':masterEventId:' . $watchlist->master_event_id, [
                'value' => $watchlist->id
            ]);
        }, $userWatchlist->toArray());
    }

    private static function db2SwtUserProviderConfig(Server $swoole)
    {
        $userProviderConfig = DB::table('user_provider_configurations')->get();
        $swooleTable        = $swoole->userProviderConfigTable;

        array_map(function ($userConfig) use ($swooleTable) {
            $swooleTable->set('userId:' . $userConfig->user_id . ':pId:' . $userConfig->provider_id, [
                'user_id'           => $userConfig->user_id,
                'provider_id'       => $userConfig->provider_id,
                'active'            => $userConfig->active,
                'punter_percentage' => $userConfig->punter_percentage,
            ]);
        }, $userProviderConfig->toArray());
    }

    private static function db2SwtActiveEvents(Server $swoole, $maxMissingCount)
    {
        $activeEventsArray = [];
        $activeEvents      = $swoole->activeEventsTable;
        $events            = DB::table('events as e')
            ->join('master_events as me', 'me.id', 'e.master_event_id')
            ->whereNull('e.deleted_at')
            ->where('e.missing_count', '<=', $maxMissingCount)
            ->select([
                'e.*',
                'e.game_schedule'
            ])
            ->get();

        array_map(function ($event) use ($activeEvents, &$activeEventsArray) {
            $activeEventsArray[$event->sport_id][$event->provider_id][$event->game_schedule][] = (string) $event->event_identifier;

            $activeEvents->set('sId:' . $event->sport_id . ':pId:' . $event->provider_id . ':schedule:' . $event->game_schedule, [
                'events' => json_encode($activeEventsArray[$event->sport_id][$event->provider_id][$event->game_schedule])
            ]);
        }, $events->toArray());
    }

    private static function db2SwtUserSelectedLeagues(Server $swoole)
    {
        $userSelectedLeaguesTable = $swoole->userSelectedLeaguesTable;
        $userSelectedLeagues      = DB::table('user_selected_leagues as usl')
            ->join('master_leagues as ml', 'ml.id', 'usl.master_league_id')
            ->select([
                'usl.user_id',
                'usl.id',
                'usl.sport_id',
                'usl.game_schedule',
                'ml.name as master_league_name',
                'usl.master_league_id'
            ])
            ->get();

        array_map(function ($userSelectedLeague) use ($userSelectedLeaguesTable) {
            $userSelectedLeaguesTable->set(
                implode(':', [
                    'userId:' . $userSelectedLeague->user_id,
                    'sId:' . $userSelectedLeague->sport_id,
                    'lId:' . $userSelectedLeague->master_league_id,
                    'schedule:' . $userSelectedLeague->game_schedule
                ]), [
                    'user_id'          => $userSelectedLeague->user_id,
                    'sport_id'         => $userSelectedLeague->sport_id,
                    'master_league_id' => $userSelectedLeague->master_league_id,
                    'schedule'         => $userSelectedLeague->game_schedule
                ]);
        }, $userSelectedLeagues->toArray());
    }

    private static function db2SwtOrders(Server $swoole)
    {
        $ordersTable = $swoole->ordersTable;
        $topicsTable = $swoole->topicTable;
        $orders      = DB::table('orders as o')
            ->join('provider_accounts AS pa', 'o.provider_account_id', '=', 'pa.id')
            ->select([
                'o.id',
                'o.status',
                'o.created_at',
                'o.bet_id',
                'o.order_expiry',
                'pa.username',
                'o.user_id'
            ])
            ->get();

        array_map(function ($order) use ($ordersTable, $topicsTable) {
            $ordersTable->set('orderId:' . $order->id, [
                'created_at'  => $order->created_at,
                'bet_id'      => $order->bet_id,
                'orderExpiry' => $order->order_expiry,
                'username'    => $order->username,
                'status'      => $order->status,
            ]);

            $topicsId = implode(':', [
                "userId:" . $order->user_id,
                "unique:" . $order->id,
            ]);

            $topicsTable->set($topicsId, [
                'user_id'    => $order->user_id,
                'topic_name' => "order-" . $order->id
            ]);

            if ($order->created_at >= Carbon::now()->subSeconds((int) $order->order_expiry)) {
                SwooleHandler::setValue('pendingOrdersWithinExpiryTable', 'orderId:' . $order->id, [
                    'user_id'      => $order->user_id,
                    'id'           => $order->id,
                    'created_at'   => $order->created_at,
                    'order_expiry' => (int) $order->order_expiry
                ]);
            }
        }, $orders->toArray());
    }

    private static function db2SwtExchangeRates(Server $swoole)
    {
        $swTable       = $swoole->exchangeRatesTable;
        $exchangeRates = DB::table('exchange_rates AS er')
            ->join('currency AS cf', 'er.from_currency_id', '=', 'cf.id')
            ->join('currency AS ct', 'er.to_currency_id', '=', 'ct.id')
            ->get([
                'er.id',
                'cf.code AS from_code',
                'ct.code AS to_code',
                'er.default_amount',
                'er.exchange_rate',
            ]);

        array_map(function ($exchangeRates) use ($swTable) {
            $erSwtId = implode(':', [
                "from:" . $exchangeRates->from_code,
                "to:" . $exchangeRates->to_code,
            ]);

            $swTable->set($erSwtId, [
                'id'             => $exchangeRates->id,
                'default_amount' => $exchangeRates->default_amount,
                'exchange_rate'  => $exchangeRates->exchange_rate,
            ]);
        }, $exchangeRates->toArray());
    }

    private static function db2SwtCurrencies(Server $swoole)
    {
        $currency = DB::table('currency')->get();
        $swTable  = $swoole->currenciesTable;

        array_map(function ($currency) use ($swTable) {
            $swtId = implode(':', [
                "currencyId:" . $currency->id,
                "currencyCode:" . $currency->code,
            ]);

            $swTable->set($swtId, [
                'id'   => $currency->id,
                'code' => $currency->code,
            ]);
        }, $currency->toArray());
    }

    private static function db2SwtUserInfo(Server $swoole)
    {
        $users   = DB::table('users')->get();
        $swTable = $swoole->usersTable;

        array_map(function ($users) use ($swTable) {
            $swtId = "userId:" . $users->id;

            $swTable->set($swtId, [
                'currency_id' => $users->currency_id,
            ]);
        }, $users->toArray());
    }

    private static function db2SwtMLBetId(Server $swoole)
    {
        $lastMLBetId = Order::orderBy('created_at', 'desc')->first();

        if (is_null($lastMLBetId)) {
            $betId = "ML" . date('Ymd') . "000001";
        } else {
            $betId = $lastMLBetId->ml_bet_identifier;
        }

        $swTable = $swoole->mlBetIdTable;

        $swTable->set('mlBetId', [
            'ml_bet_id' => $betId,
        ]);
    }

    private static function db2SwtMaintenance(Server $swoole)
    {
        $providers   = Provider::all();
        $maintenance = $swoole->maintenanceTable;

        foreach ($providers AS $row) {
            $maintenance->set('maintenance:' . strtolower($row->alias), [
                'provider'          => strtoupper($row->alias),
                'under_maintenance' => "false",
            ]);
        }
    }
}
