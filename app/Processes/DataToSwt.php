<?php

namespace App\Processes;

use App\Models\Sport;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\{DB, Log};
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
            'Providers',
            'MasterLeagues',
            'MasterTeams',
            'SportOddTypes',
            'MasterEvents',
            'MasterEventMarkets',
            'UserWatchlist',
            'UserProviderConfig',
            'ActiveEvents',
            'UserSelectedLeagues',
            'Orders',
            'ExchangeRates',
            'Currencies',
            'UserInfo',
            'ProviderAccounts'
        ];

        foreach ($swooleProcesses as $process) {
            $method = "db2Swt" . $process;
            self::{$method}($swoole);
        }

        $swoole->data2SwtTable->set('data2Swt', ['value' => 1]);

        while (!self::$quit) {
            usleep(100000);
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

        // Odd Types
        $oddTypes      = DB::table('odd_types')->get();
        $oddTypesTable = $swoole->oddTypesTable;
        array_map(function ($oddType) use ($oddTypesTable) {
            $oddTypesTable->set('oddType:' . $oddType->type, ['id' => $oddType->id, 'type' => $oddType->type]);
        }, $oddTypes->toArray());
    }

    private static function db2SwtProviders(Server $swoole)
    {
        $providers      = DB::table('providers')->orderBy('priority', 'asc')->get();
        $providersTable = $swoole->providersTable;
        array_map(function ($provider) use ($providersTable) {
            $providersTable->set('providerAlias:' . strtolower($provider->alias),
                [
                    'id'                => $provider->id,
                    'alias'             => $provider->alias,
                    'priority'          => $provider->priority,
                    'is_enabled'        => $provider->is_enabled,
                    'currency_id'       => $provider->currency_id,
                    'punter_percentage' => $provider->punter_percentage,
                ]);
        }, $providers->toArray());
    }

    private static function db2SwtMasterLeagues(Server $swoole)
    {
        /** TODO: table source will be changed */
        $leagues      = DB::table('master_leagues')
            ->join('master_league_links', 'master_leagues.id', 'master_league_links.master_league_id')
            ->whereNull('master_leagues.deleted_at')
            ->select('master_leagues.id', 'master_leagues.sport_id', 'master_leagues.master_league_name',
                'master_league_links.league_name',
                'master_league_links.provider_id', 'master_leagues.updated_at')
            ->get();
        $leaguesTable = $swoole->leaguesTable;
        array_map(function ($league) use ($leaguesTable) {
            $leagueLookUpId = uniqid();
            app('swoole')->leagueLookUpTable->set('leagueLookUpId:' . $leagueLookUpId,
                ['value' => $league->league_name]);
            $leaguesTable->set('sId:' . $league->sport_id . ':pId:' . $league->provider_id . ':leagueLookUpId:' . $leagueLookUpId,
                [
                    'id'                 => $league->id,
                    'sport_id'           => $league->sport_id,
                    'provider_id'        => $league->provider_id,
                    'master_league_name' => $league->master_league_name,
                    'league_name'        => $league->league_name,
                ]
            );
        }, $leagues->toArray());
    }

    private static function db2SwtMasterTeams(Server $swoole)
    {
        $teams      = DB::table('master_teams')
            ->join('master_team_links', 'master_team_links.master_team_id', 'master_teams.id')
            ->select('master_teams.id', 'master_team_links.team_name', 'master_teams.master_team_name',
                'master_team_links.provider_id')
            ->get();
        $teamsTable = $swoole->teamsTable;
        array_map(function ($team) use ($teamsTable) {
            $teamLookUpId = uniqid();
            app('swoole')->teamLookUpTable->set('teamLookUpId:' . $teamLookUpId, ['value' => $team->team_name]);
            $teamsTable->set('pId:' . $team->provider_id . ':teamLookUpId:' . $teamLookUpId,
                [
                    'id'               => $team->id,
                    'team_name'        => $team->team_name,
                    'master_team_name' => $team->master_team_name,
                    'provider_id'      => $team->provider_id
                ]);
        }, $teams->toArray());
    }

    private static function db2SwtSportOddTypes(Server $swoole)
    {
        $sportOddTypes      = DB::table('sport_odd_type')
            ->join('odd_types', 'odd_types.id', 'sport_odd_type.odd_type_id')
            ->join('sports', 'sports.id', 'sport_odd_type.sport_id')
            ->select('sport_odd_type.sport_id', 'sport_odd_type.odd_type_id', 'odd_types.type', 'sport_odd_type.id')
            ->get();
        $sportOddTypesTable = $swoole->sportOddTypesTable;
        array_map(function ($sportOddType) use ($sportOddTypesTable) {
            $sportOddTypesTable->set('sId:' . $sportOddType->sport_id . ':oddType:' . Str::slug($sportOddType->type),
                [
                    'id'                => $sportOddType->id,
                    'sportId'           => $sportOddType->sport_id,
                    'sport_odd_type_id' => $sportOddType->id,
                    'type'              => $sportOddType->type
                ]);
        }, $sportOddTypes->toArray());
    }

    private static function db2SwtMasterEvents(Server $swoole)
    {
        $masterEvents      = DB::table('master_events')
            ->join('sports', 'sports.id', 'master_events.sport_id')
            ->join('master_event_links', 'master_event_links.master_event_unique_id',
                'master_events.master_event_unique_id')
            ->join('events', 'events.id', 'master_event_links.event_id')
            ->join('master_leagues', 'master_leagues.master_league_name', 'master_events.master_league_name')
            ->whereNull('master_events.deleted_at')
            ->select('master_events.id', 'master_events.master_event_unique_id', 'events.provider_id',
                'events.event_identifier', 'master_leagues.id as master_league_id', 'master_events.sport_id',
                'master_events.ref_schedule', 'master_events.game_schedule', 'master_events.master_home_team_name',
                'master_events.master_away_team_name', 'master_leagues.master_league_name', 'master_events.score',
                'master_events.running_time', 'master_events.home_penalty', 'master_events.away_penalty')
            ->get();
        $masterEventsTable = $swoole->eventsTable;
        array_map(function ($event) use ($masterEventsTable) {
            $masterEventsTable->set('sId:' . $event->sport_id . ':pId:' . $event->provider_id . ':eventIdentifier:' . $event->event_identifier,
                [
                    'id'                     => $event->id,
                    'event_identifier'       => $event->event_identifier,
                    'sport_id'               => $event->sport_id,
                    'provider_id'            => $event->provider_id,
                    'master_event_unique_id' => $event->master_event_unique_id,
                    'master_home_team_name'  => $event->master_home_team_name,
                    'master_away_team_name'  => $event->master_away_team_name,
                    'ref_schedule'           => $event->ref_schedule,
                    'game_schedule'          => $event->game_schedule,
                    'master_league_name'     => $event->master_league_name,
                    'score'                  => $event->score,
                    'running_time'           => $event->running_time,
                    'home_penalty'           => $event->home_penalty,
                    'away_penalty'           => $event->away_penalty,
                ]);
        }, $masterEvents->toArray());
    }

    private static function db2SwtMasterEventMarkets(Server $swoole)
    {
        $masterEventMarkets      = DB::table('master_event_markets')
            ->join('master_event_market_links', 'master_event_market_links.master_event_market_unique_id',
                'master_event_markets.master_event_market_unique_id')
            ->join('event_markets', 'event_markets.id', 'master_event_market_links.event_market_id')
            ->join('master_events', 'master_events.master_event_unique_id',
                'master_event_markets.master_event_unique_id')
            ->join('odd_types', 'odd_types.id', 'master_event_markets.odd_type_id')
            ->select('event_markets.id', 'master_event_markets.master_event_unique_id',
                'master_event_markets.master_event_market_unique_id',
                'master_event_market_links.event_market_id',
                'event_markets.odd_type_id', 'event_markets.provider_id',
                'event_markets.odds', 'event_markets.odd_label', 'event_markets.bet_identifier',
                'event_markets.is_main', 'event_markets.market_flag')
            ->get();
        $masterEventMarketsTable = $swoole->eventMarketsTable;
        array_map(function ($eventMarket) use ($masterEventMarketsTable) {
            $odds = $eventMarket->bet_identifier == "" ? 0 : (float)$eventMarket->odds;

            $masterEventMarketsTable->set(
                'pId:' . $eventMarket->provider_id .
                ':meUID:' . $eventMarket->master_event_unique_id .
                ':bId:' . $eventMarket->bet_identifier,
                [
                    'id'                            => $eventMarket->id,
                    'master_event_unique_id'        => $eventMarket->master_event_unique_id,
                    'master_event_market_unique_id' => $eventMarket->master_event_market_unique_id,
                    'odd_type_id'                   => $eventMarket->odd_type_id,
                    'provider_id'                   => $eventMarket->provider_id,
                    'odds'                          => $odds,
                    'odd_label'                     => $eventMarket->odd_label,
                    'bet_identifier'                => $eventMarket->bet_identifier,
                    'is_main'                       => $eventMarket->is_main,
                    'market_flag'                   => $eventMarket->market_flag,
                ]);
        }, $masterEventMarkets->toArray());
    }

    private static function db2SwtUserWatchlist(Server $swoole)
    {
        $userWatchlist      = DB::table('user_watchlist')
            ->get();
        $userWatchlistTable = $swoole->userWatchlistTable;
        array_map(function ($watchlist) use ($userWatchlistTable) {
            $userWatchlistTable->set(
                'userWatchlist:' . $watchlist->user_id .
                ':masterEventUniqueId:' . $watchlist->master_event_unique_id,
                ['value' => $watchlist->id]);
        }, $userWatchlist->toArray());
    }

    private static function db2SwtUserProviderConfig(Server $swoole)
    {
        $swooleTable        = $swoole->userProviderConfigTable;
        $userProviderConfig = DB::table('user_provider_configurations')
            ->get();

        array_map(function ($userConfig) use ($swooleTable) {
            $swooleTable->set('userId:' . $userConfig->user_id . ':pId:' . $userConfig->provider_id,
                [
                    'user_id'     => $userConfig->user_id,
                    'provider_id' => $userConfig->provider_id,
                    'active'      => $userConfig->active,
                ]
            );
        }, $userProviderConfig->toArray());
    }

    private static function db2SwtActiveEvents(Server $swoole)
    {
        $events       = DB::table('events')
            ->whereNull('deleted_at')
            ->get();
        $activeEvents = $swoole->activeEventsTable;
        $activeEventsArray = [];
        array_map(function ($event) use ($activeEvents, $activeEventsArray) {
            $activeEventsArray[$event->sport_id][$event->provider_id][$event->game_schedule][] = $event->event_identifier;
            $activeEvents->set('sId:' . $event->sport_id . ':pId:' . $event->provider_id . ':schedule:' . $event->game_schedule,
                ['events' => json_encode($activeEventsArray[$event->sport_id][$event->provider_id][$event->game_schedule])]);
        }, $events->toArray());

        foreach($activeEvents as $key => $row) {
            Log::debug('ACTIVE EVENT KEY:' . $key);
            Log::debug('ACTIVE EVENT VALUES:' . json_encode($row));
        }
    }

    private static function db2SwtUserSelectedLeagues(Server $swoole)
    {
        $userSelectedLeagues      = DB::table('user_selected_leagues')
            ->get();
        $userSelectedLeaguesTable = $swoole->userSelectedLeaguesTable;
        array_map(function ($userSelectedLeague) use ($userSelectedLeaguesTable) {
            $userSelectedLeaguesTable->set(
                implode(':', [
                    'userId:' . $userSelectedLeague->user_id,
                    'sId:' . $userSelectedLeague->sport_id,
                    'schedule:' . $userSelectedLeague->game_schedule,
                    'uniqueId:' . uniqid()
                ]), [
                'userId'      => $userSelectedLeague->user_id,
                'sId'         => $userSelectedLeague->sport_id,
                'schedule'    => $userSelectedLeague->game_schedule,
                'league_name' => $userSelectedLeague->master_league_name
            ]);
        }, $userSelectedLeagues->toArray());
    }

    private static function db2SwtOrders(Server $swoole)
    {
        $orders = DB::table('orders as o')
            ->join('provider_accounts AS pa', 'o.provider_account_id', '=', 'pa.id')
            ->join('master_event_markets as mem', 'mem.master_event_market_unique_id',
                'o.master_event_market_unique_id')
            ->join('master_events as me', 'me.master_event_unique_id', 'mem.master_event_unique_id')
            ->join('master_event_links as mel', 'mel.master_event_unique_id', 'me.master_event_unique_id')
            ->join('events as e', 'e.id', 'mel.event_id')
            ->select([
                'o.id',
                'o.status',
                'o.stake',
                'o.created_at',
                'o.actual_stake',
                'o.odds',
                'o.market_id',
                'mem.master_event_unique_id',
                'mem.master_event_market_unique_id',
                'me.score',
                'o.bet_id',
                'o.order_expiry',
                'pa.id AS provider_account_id',
                'pa.username',
            ])
            ->get();

        $ordersTable = $swoole->ordersTable;

        array_map(function ($order) use ($ordersTable) {
            $ordersTable->set('orderId:' . $order->id, [
                'created_at'  => $order->created_at,
                'bet_id'      => $order->bet_id,
                'orderExpiry' => $order->order_expiry,
                'username'    => $order->username,
                'status'      => $order->status,
            ]);
        }, $orders->toArray());
    }

    private static function db2SwtExchangeRates(Server $swoole)
    {
        $exchangeRates = DB::table('exchange_rates AS er')
            ->join('currency AS cf', 'er.from_currency_id', '=', 'cf.id')
            ->join('currency AS ct', 'er.to_currency_id', '=', 'ct.id')
            ->get([
                'er.from_currency_id',
                'er.to_currency_id',
                'cf.code AS from_code',
                'ct.code AS to_code',
                'er.default_amount',
                'er.exchange_rate',
            ]);

        $swTable = $swoole->exchangeRatesTable;

        array_map(function ($exchangeRates) use ($swTable) {
            $erSwtId = implode(':', [
                "from:" . $exchangeRates->from_code,
                "to:" . $exchangeRates->to_code,
            ]);

            $swTable->set($erSwtId, [
                'default_amount' => $exchangeRates->default_amount,
                'exchange_rate'  => $exchangeRates->exchange_rate,
            ]);
        }, $exchangeRates->toArray());
    }

    private static function db2SwtCurrencies(Server $swoole)
    {
        $currency = DB::table('currency')
            ->get();

        $swTable = $swoole->currenciesTable;

        array_map(function ($currency) use ($swTable) {
            $swtId = implode(':', [
                "currencycId:" . $currency->id,
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
        $users = DB::table('users')
            ->get();

        $swTable = $swoole->usersTable;

        array_map(function ($users) use ($swTable) {
            $swtId = "userId:" . $users->id;

            $swTable->set($swtId, [
                'currency_id' => $users->currency_id,
            ]);
        }, $users->toArray());
    }

    private static function db2SwtProviderAccounts(Server $swoole)
    {
        $providerAccounts = DB::table('provider_accounts as pa')
            ->join('providers as p', 'p.id', 'pa.provider_id')
            ->select('pa.id', 'pa.provider_id', 'pa.type', 'pa.username', 'pa.password', 'pa.punter_percentage',
                'pa.credits', 'p.alias')
            ->get();

        $providerAccountsTable = $swoole->providerAccountsTable;

        array_map(function ($providerAccount) use ($providerAccountsTable) {
            $swtId = implode(':', [
                "providerId:" . $providerAccount->provider_id,
                'uniqueId:' . uniqid()
            ]);

            $providerAccountsTable->set($swtId, [
                'id'                => $providerAccount->id,
                'provider_id'       => $providerAccount->provider_id,
                'provider_alias'    => $providerAccount->alias,
                'type'              => $providerAccount->type,
                'username'          => $providerAccount->username,
                'password'          => $providerAccount->password,
                'punter_percentage' => $providerAccount->punter_percentage,
                'credits'           => $providerAccount->credits,
            ]);
        }, $providerAccounts->toArray());
    }
}
