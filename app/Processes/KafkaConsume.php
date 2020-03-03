<?php

namespace App\Processes;

use App\Jobs\Data2SWT;
use App\Jobs\TransformKafkaMessage;
use App\Models\Sport;
use Faker\Factory;
use Faker\Generator;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Swoole\Http\Server;
use Swoole\Process;

class KafkaConsume implements CustomProcessInterface
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
            'Transformed',
            'UserWatchlist'
        ];
        foreach ($swooleProcesses as $process) {
            $method = "db2Swt" . $process;
            self::{$method}($swoole);
        }

        $kafkaTable = $swoole->kafkaTable;

        $kafkaConsumer = resolve('KafkaConsumer');
        $kafkaConsumer->subscribe([env('KAFKA_SCRAPE_ODDS')]);
        while (!self::$quit) {
            $message = $kafkaConsumer->consume(120 * 1000);
            if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {

                 Log::debug(json_encode($message));
                 $kafkaTable->set('message:' . $message->offset, ['value' => $message->payload]);
                 Log::debug(json_encode($kafkaTable->get('message:' . $message->offset)));

                TransformKafkaMessage::dispatch($message);

                $kafkaConsumer->commitAsync($message);
            } else {
                Log::error(json_encode([$message]));
            }

            self::getAdditionalLeagues($swoole);
            self::getForRemovallLeagues($swoole);
            self::getUpdatedOdds($swoole);
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }

    private static function db2SwtSports(Server $swoole)
    {
        $sports = Sport::getActiveSports()->get();
        $sportsTable = $swoole->sportsTable;
        array_map(function ($sport) use ($sportsTable) {
            $sportsTable->set('sId:' . $sport['id'], ['sport' => $sport['sport'], 'id' => $sport['id']]);
        }, $sports->toArray());

        // Odd Types
        $oddTypes = DB::table('odd_types')->get();
        $oddTypesTable = $swoole->oddTypesTable;
        array_map(function ($oddType) use ($oddTypesTable) {
            $oddTypesTable->set('oddType:' . $oddType->type, ['id' => $oddType->id, 'type' => $oddType->type]);
        }, $oddTypes->toArray());
    }

    private static function db2SwtProviders(Server $swoole)
    {
        $providers = DB::table('providers')->get();
        $providersTable = $swoole->providersTable;
        array_map(function ($provider) use ($providersTable) {
            $providersTable->set('providerAlias:' . strtolower($provider->alias),
                ['id' => $provider->id, 'alias' => $provider->alias, 'priority' => $provider->priority, 'is_enabled' => $provider->is_enabled]);
        }, $providers->toArray());
    }

    private static function db2SwtMasterLeagues(Server $swoole)
    {
        /** TODO: table source will be changed */
        $leagues = DB::table('master_leagues')
            ->join('master_league_links', 'master_leagues.id', 'master_league_links.master_league_id')
            ->whereNull('master_leagues.deleted_at')
            ->select('master_leagues.id', 'master_leagues.sport_id', 'master_leagues.master_league_name',
                'master_league_links.league_name',
                'master_league_links.provider_id', 'master_leagues.updated_at')
            ->get();
        $leaguesTable = $swoole->leaguesTable;
        array_map(function ($league) use ($leaguesTable) {
            $leagueLookUpId = uniqid();
            app('swoole')->wsTable->set('leagueLookUpId:' . $leagueLookUpId, ['value' => $league->league_name]);
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
        $teams = DB::table('master_teams')
            ->join('master_team_links', 'master_team_links.master_team_id', 'master_teams.id')
            ->select('master_teams.id', 'master_team_links.team_name', 'master_teams.master_team_name',
                'master_team_links.provider_id')
            ->get();
        $teamsTable = $swoole->teamsTable;
        array_map(function ($team) use ($teamsTable) {
            $teamsTable->set('pId:' . $team->provider_id . ':teamName:' . Str::slug($team->team_name),
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
        $sportOddTypes = DB::table('sport_odd_type')
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
        $masterEvents = DB::table('master_events')
            ->join('sports', 'sports.id', 'master_events.sport_id')
            ->join('master_event_links', 'master_event_links.master_event_unique_id',
                'master_events.master_event_unique_id')
            ->join('events', 'events.id', 'master_event_links.event_id')
            ->join('master_leagues', 'master_leagues.master_league_name', 'master_events.master_league_name')
            ->select('master_events.id', 'master_events.master_event_unique_id', 'events.provider_id',
                'events.event_identifier', 'master_leagues.id as master_league_id', 'master_events.sport_id',
                'master_events.ref_schedule', 'master_events.master_home_team_name',
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
        $masterEventMarkets = DB::table('master_event_markets')
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
            $masterEventMarketsTable->set(
                'pId:' . $eventMarket->provider_id .
                ':meUID:' . $eventMarket->master_event_unique_id .
                ':betIdentifier:' . $eventMarket->bet_identifier,
                [
                    'id'                            => $eventMarket->id,
                    'master_event_unique_id'        => $eventMarket->master_event_unique_id,
                    'master_event_market_unique_id' => $eventMarket->master_event_market_unique_id,
                    'odd_type_id'                   => $eventMarket->odd_type_id,
                    'provider_id'                   => $eventMarket->provider_id,
                    'odds'                          => $eventMarket->odds,
                    'odd_label'                     => $eventMarket->odd_label,
                    'bet_identifier'                => $eventMarket->bet_identifier,
                    'is_main'                       => $eventMarket->is_main,
                    'market_flag'                   => $eventMarket->market_flag,
                ]);
        }, $masterEventMarkets->toArray());
    }

    private static function db2SwtTransformed(Server $swoole)
    {
        $transformed = DB::table('master_leagues as ml')
            ->join('sports as s', 's.id', 'ml.sport_id')
            ->join('master_events as me', 'me.master_league_name', 'ml.master_league_name')
            ->join('master_event_markets as mem', 'mem.master_event_unique_id', 'me.master_event_unique_id')
            ->join('odd_types as ot', 'ot.id', 'mem.odd_type_id')
            ->join('master_event_market_links as meml', 'meml.master_event_market_unique_id', 'mem.master_event_market_unique_id')
            ->join('event_markets as em', 'em.id', 'meml.event_market_id')
            ->select('ml.sport_id', 'ml.master_league_name', 's.sport', //'mll.provider_id',
                'me.master_event_unique_id', 'me.master_home_team_name', 'me.master_away_team_name',
                'me.ref_schedule', 'me.game_schedule', 'me.score', 'me.running_time',
                'me.home_penalty', 'me.away_penalty', 'mem.odd_type_id', 'mem.master_event_market_unique_id', 'mem.is_main', 'mem.market_flag',
                'ot.type', 'em.odds', 'em.odd_label', 'em.provider_id')
            ->distinct()->get();
        $data = [];
        array_map(function ($transformed) use (&$data) {
            $mainOrOther = $transformed->is_main ? 'main' : 'other';
            if (empty($data[$transformed->master_event_unique_id])) {
                $data[$transformed->master_event_unique_id] = [
                    'uid' => $transformed->master_event_unique_id,
                    'sport_id' => $transformed->sport_id,
                    'sport' => $transformed->sport,
                    'provider_id' => $transformed->provider_id,
                    'game_schedule' => $transformed->game_schedule,
                    'league_name' => $transformed->master_league_name,
                    'running_time' => $transformed->running_time,
                    'ref_schedule' => $transformed->ref_schedule,
                ];
            }

            if (empty($data[$transformed->master_event_unique_id]['home'])) {
                $data[$transformed->master_event_unique_id]['home'] = [
                    'name' => $transformed->master_home_team_name,
                    'score' => empty($transformed->score) ? '' : array_values(explode(' - ', $transformed->score))[0],
                    'redcard' => $transformed->home_penalty
                ];
            }

            if (empty($data[$transformed->master_event_unique_id]['away'])) {
                $data[$transformed->master_event_unique_id]['away'] = [
                    'name' => $transformed->master_away_team_name,
                    'score' => empty($transformed->score) ? '' : array_values(explode(' - ', $transformed->score))[1],
                    'redcard' => $transformed->home_penalty
                ];
            }

            if (empty($data[$transformed->master_event_unique_id]['market_odds'][$mainOrOther][$transformed->type][$transformed->market_flag])) {
                $data[$transformed->master_event_unique_id]['market_odds'][$mainOrOther][$transformed->type][$transformed->market_flag] = [
                    'odds' => (double) $transformed->odds,
                    'market_id' => $transformed->master_event_market_unique_id
                ];
                if (!empty($transformed->odd_label)) {
                    $data[$transformed->master_event_unique_id]['market_odds'][$mainOrOther][$transformed->type][$transformed->market_flag]['points'] = $transformed->odd_label;
                }
            }

        }, $transformed->toArray());

        foreach ($data as $key => $_data) {
            $swoole->transformedTable->set('uid:' . $key . ":pId:" . $_data['provider_id'], ['value' => json_encode($_data)]);
        }
    }

    private static function db2SwtUserWatchlist(Server $swoole)
    {
        $userWatchlist = DB::table('user_watchlist')
            ->get();
        $wsTable = $swoole->wsTable;
        array_map(function ($watchlist) use ($wsTable) {
            $wsTable->set(
                'userWatchlist:' . $watchlist->user_id .
                ':masterEventUniqueId:' . $watchlist->master_event_unique_id,
                ['value' => $watchlist->id]);
        }, $userWatchlist->toArray());
    }

    private static function getAdditionalLeagues($swoole)
    {
        $leaguesData = [];
        $table = $swoole->wsTable;
        foreach ($table as $key => $row) {
            if (strpos($key, 'fd:') === 0) {
                $sports = $swoole->sportsTable;
                foreach ($sports as $sport) {
                    if ($swoole->wsTable->exist('userAdditionalLeagues:' . $row['value'] . ':sportId:' . $sport['id'])) {
                        $userAdditionalLeague = $swoole->wsTable->get('userAdditionalLeagues:' . $row['value'] . ':sportId:' . $sport['value']);
                        $leagues = $swoole->leaguesTable;
                        foreach ($leagues as $key => $league) {
                            if (strpos($key, $sport['value'] . ':') === 0) {
                                if ($league['timestamp'] > $userAdditionalLeague['value']) {
                                    $leaguesData[] = [
                                        'name'        => $league['multi_league'],
                                        'match_count' => $league['match_count']
                                    ];
                                }
                            }
                        }
                    }
                }
                if (!empty($leaguesData)) {
                    $fd = $swoole->wsTable->get('uid:' . $row['value']);
                    $swoole->push($fd['value'], json_encode(['getAdditionalLeagues' => $leaguesData]));
                }
            }
        }
    }

    private static function getForRemovallLeagues($swoole)
    {
        $leagues = [];
        $table = $swoole->wsTable;
        foreach ($table as $key => $row) {
            if (strpos($key, 'fd:') === 0) {
                $sports = $swoole->sportsTable;
                foreach ($sports as $sport) {
                    $deletedLeagues = $swoole->deletedLeaguesTable;
                    foreach ($deletedLeagues as $key => $league) {
                        $leagues[] = [
                            'league' => str_replace('sportId:' . $sport['value'] . ':league:',
                                '', $key)
                        ];
                    }
                }
                if (!empty($leagues)) {
                    $fd = $swoole->wsTable->get('uid:' . $row['value']);
                    $swoole->push($fd['value'], json_encode(['getForRemovalLeagues' => $leagues]));
                }
            }
        }
    }

    private static function getUpdatedOdds($swoole)
    {
        $table = $swoole->wsTable;
        foreach ($table as $k => $r) {
            if (strpos($k, 'updatedEvents:') === 0) {
                foreach ($table as $key => $row) {
                    $updatedMarkets = json_decode($r['value']);
                    if (!empty($updatedMarkets)) {
                        if (strpos($key, 'fd:') === 0) {
                            $fd = $table->get('uid:' . $row['value']);
                            $swoole->push($fd['value'], json_encode(['getUpdatedOdds' => $updatedMarkets]));
                            $table->del($k);
                        }
                    }
                }
            }
        }
    }

    private static function testData() {
        $faker = Factory::create();
        return json_encode(array (
            'request_uid' => '0c6e25d7-df8c-4cb1-8776-a911f3aebecd',
            'request_ts' => '1583209828.45480400',
            'command' => 'odd',
            'sub_command' => 'transform',
            'data' =>
                array (
                    'provider' => 'hg',
                    'schedule' => 'today',
                    'sport' => 2,
                    'leagueName' => 'NCAA Basketball',
                    'homeTeam' => 'Oklahoma',
                    'awayTeam' => 'Texas',
                    'referenceSchedule' => '2020-03-04T02:00:00.000+04:00',
                    'running_time' => '',
                    'home_score' => 0,
                    'away_score' => 0,
                    'home_redcard' => 0,
                    'away_redcard' => 0,
                    'id' => 49,
                    'events' =>
                        array (
                            0 =>
                                array (
                                    'eventId' => '3472289',
                                    'market_type' => 1,
                                    'market_odds' =>
                                        array (
                                            0 =>
                                                array (
                                                    'oddsType' => 'ML',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'MH3472289',
                                                                    'indicator' => 'Home',
                                                                    'odds' => '2.3',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'MC3472289',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '1.3',
                                                                ),
                                                        ),
                                                ),
                                            1 =>
                                                array (
                                                    'oddsType' => 'HDP',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'RH3472289',
                                                                    'indicator' => 'Home',
                                                                    'odds' => $faker->randomFloat(99.99),//'0.909',
                                                                    'points' => '-6.5',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'RC3472289',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '0.891',
                                                                    'points' => '+6.5',
                                                                ),
                                                        ),
                                                ),
                                            2 =>
                                                array (
                                                    'oddsType' => 'OU',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'OUC3472289',
                                                                    'indicator' => 'Home',
                                                                    'odds' => $faker->randomFloat(99.99),
                                                                    'points' => 'O 132.5',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'OUH3472289',
                                                                    'indicator' => 'Away',
                                                                    'odds' => $faker->randomFloat(99.99),
                                                                    'points' => 'U 132.5',
                                                                ),
                                                        ),
                                                ),
                                            3 =>
                                                array (
                                                    'oddsType' => 'HOME GOALS',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'OUHO3472289',
                                                                    'indicator' => 'Home',
                                                                    'odds' => '0.880',
                                                                    'points' => 'O 69.5',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'OUHU3472289',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '0.880',
                                                                    'points' => 'U 69.5',
                                                                ),
                                                        ),
                                                ),
                                            4 =>
                                                array (
                                                    'oddsType' => 'AWAY GOALS',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'OUCO3472289',
                                                                    'indicator' => 'Home',
                                                                    'odds' => '0.870',
                                                                    'points' => 'O 63',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'OUCU3472289',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '0.890',
                                                                    'points' => 'U 63',
                                                                ),
                                                        ),
                                                ),
                                        ),
                                ),
                        ),
                ),
        ));
    }
}
