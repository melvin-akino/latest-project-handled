<?php

namespace App\Processes;

use App\Facades\SwooleHandler;
use App\Models\{Game, UserProviderConfiguration, UserSelectedLeague, UserWatchlist};
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;

class SwtToWs implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            if ($swoole->data2SwtTable->exist('data2Swt')) {
                $i = 0;
                while (!self::$quit) {
                    self::getUpdatedOdds($swoole);
                    self::getEventSectionRemoved($swoole);
                    self::getEventScored($swoole);
                    self::getEventScoredWithOdds($swoole);

                    if ($i % 5 == 0) {
                        self::getInActiveEvents($swoole);
                        // self::getForBetBarRemoval($swoole);
                    }
                    usleep(1000000);
                    $i++;
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }

    private static function getUpdatedOdds($swoole)
    {
        $updatedEventsTable = SwooleHandler::table('updatedEventsTable');
        $wsTable            = SwooleHandler::table('wsTable');
        $topicTable         = SwooleHandler::table('topicTable');
        foreach ($updatedEventsTable as $k => $r) {
            $updatedMarkets = json_decode($r['odds']);
            $providerId     = $r['provider_id'];
            $uid            = substr($k, strlen('updatedEvents:'));
            $eventsInfo     = SwooleHandler::getValue('eventsInfoTable', 'eventsInfo:' . $uid);
            $event          = json_decode($eventsInfo['value'], true);

            $userSelectedLeagues = UserSelectedLeague::getSelectedLeagueByAllUsers([
                'league_id' => $event['master_league_id'],
                'schedule'  => $event['schedule'],
                'sport_id'  => $event['sport_id']
            ]);
            if ($userSelectedLeagues->exists()) {
                foreach ($userSelectedLeagues->get() as $userSelectedLeague) {
                    $swtKey = 'userId:' . $userSelectedLeague->user_id . ':sId:' . $event['sport_id'] . ':lId:' . $event['master_league_id'] . ':schedule:' . $event['schedule'];
                    if (SwooleHandler::exists('userSelectedLeaguesTable', $swtKey)) {
                        $fd = $swoole->wsTable->get('uid:' . $userSelectedLeague->user_id);
                        if ($swoole->isEstablished($fd['value'])) {
                            $swoole->push($fd['value'], json_encode(['getEventsUpdate' => $event]));
                        }
                    }
                }
            }

            if (!empty($updatedMarkets)) {
                foreach ($topicTable as $topic) {
                    if (strpos($topic['topic_name'], 'uid-' . $uid) === 0) {
                        $fd              = $wsTable->get('uid:' . $topic['user_id']);
                        $userProviderIds = UserProviderConfiguration::getProviderIdList($topic['user_id']);

                        if (in_array($providerId, $userProviderIds) && $swoole->isEstablished($fd['value'])) {
                            $swoole->push($fd['value'], json_encode(['getEventHasOtherMarket' => [
                                'uid'               => $uid,
                                'has_other_markets' => Game::checkIfHasOtherMarkets($uid, $userProviderIds)
                            ]]));
                        }

                        if (in_array($providerId, $userProviderIds) && $swoole->isEstablished($fd['value'])) {
                            $swoole->push($fd['value'], json_encode(['getUpdatedOdds' => $updatedMarkets]));
                        }
                    }
                }
            }
            SwooleHandler::remove('updatedEventsTable', $k);
            SwooleHandler::remove('eventsInfoTable', 'eventsInfo:' . $uid);
        }
    }

    private static function getUpdatedLeagues()
    {
//        if (SwooleHandler::exists('updateLeaguesTable', 'updateLeagues')) {
//            SwooleHandler::remove('updateLeaguesTable', 'updateLeagues');
            wsEmit(['getUpdatedLeagues' => [
                'status' => true
            ]]);
//        }
    }

    // private static function getForBetBarRemoval($swoole)
    // {
    //     $topicTable        = $swoole->topicTable;
    //     $userForRemovalBet = [];
    //     foreach ($topicTable as $key => $topic) {
    //         if (strpos($topic['topic_name'], 'removal-bet-') === 0) {
    //             $userForRemovalBet[$topic['user_id']] = true;
    //             SwooleHandler::remove('topicTable', $key);
    //         }
    //     }

    //     if (!empty($userForRemovalBet)) {
    //         foreach ($userForRemovalBet as $userId => $bet) {
    //             $fd = $swoole->wsTable->get('uid:' . $userId);
    //             if ($swoole->isEstablished($fd['value'])) {
    //                 $swoole->push($fd['value'], json_encode([
    //                     'forBetBarRemoval' => ['status' => true]
    //                 ]));
    //             }
    //         }
    //     }
    // }

    private static function getEventScored($swoole)
    {
        $eventsScoredTable = $swoole->eventsScoredTable;
        foreach ($eventsScoredTable as $key => $event) {
            $userSelectedLeagues = UserSelectedLeague::getSelectedLeagueByAllUsers([
                'league_id' => $event['master_league_id'],
                'schedule'  => $event['schedule'],
                'sport_id'  => $event['sport_id']
            ]);
            if ($userSelectedLeagues->exists()) {
                foreach ($userSelectedLeagues->get() as $userSelectedLeague) {
                    $swtKey = 'userId:' . $userSelectedLeague->user_id . ':sId:' . $event['sport_id'] . ':lId:' . $event['master_league_id'] . ':schedule:' . $event['schedule'];
                    if (SwooleHandler::exists('userSelectedLeaguesTable', $swtKey)) {
                        $fd = $swoole->wsTable->get('uid:' . $userSelectedLeague->user_id);
                        if ($swoole->isEstablished($fd['value'])) {
                            $swoole->push($fd['value'], json_encode([
                                'getForRemovalOdds' => ['uid' => $event['uid']]
                            ]));
                        }
                    }
                }
            }

            $userWatchlist = UserWatchlist::getByUid($event['uid']);
            if ($userWatchlist->exists()) {
                foreach ($userWatchlist->get() as $userWatchlistData) {
                    $swtKey = "userWatchlist:" . $userWatchlistData->user_id . ":masterEventId:" . $userWatchlistData->master_event_id;
                    if (SwooleHandler::exists('userWatchlistTable', $swtKey)) {
                        $fd = $swoole->wsTable->get('uid:' . $userWatchlistData->user_id);
                        if ($swoole->isEstablished($fd['value'])) {
                            $swoole->push($fd['value'], json_encode([
                                'getForRemovalOdds' => ['uid' => $event['uid']]
                            ]));
                        }
                    }
                }
            }

            SwooleHandler::remove('eventsScoredTable', $key);
        }
    }

    private static function getEventScoredWithOdds($swoole)
    {
        $eventHasMarketsTable = SwooleHandler::table('eventHasMarketsTable');
        foreach ($eventHasMarketsTable as $key => $event) {
            if ($event['has_markets'] == 1) {
                $userSelectedLeagues = UserSelectedLeague::getSelectedLeagueByAllUsers([
                    'league_id' => $event['master_league_id'],
                    'schedule'  => $event['schedule'],
                    'sport_id'  => $event['sport_id']
                ]);
                if ($userSelectedLeagues->exists()) {
                    foreach ($userSelectedLeagues->get() as $userSelectedLeague) {
                        $swtKey = 'userId:' . $userSelectedLeague->user_id . ':sId:' . $event['sport_id'] . ':lId:' . $event['master_league_id'] . ':schedule:' . $event['schedule'];
                        if (SwooleHandler::exists('userSelectedLeaguesTable', $swtKey)) {
                            $fd = $swoole->wsTable->get('uid:' . $userSelectedLeague->user_id);
                            if ($swoole->isEstablished($fd['value'])) {
                                $swoole->push($fd['value'], json_encode([
                                    'getEventData' => ['uid' => $event['uid']]
                                ]));
                            }
                        }
                    }
                }

                $userWatchlist = UserWatchlist::getByUid($event['uid']);
                if ($userWatchlist->exists()) {
                    foreach ($userWatchlist->get() as $userWatchlistData) {
                        $swtKey = "userWatchlist:" . $userWatchlistData->user_id . ":masterEventId:" . $userWatchlistData->master_event_id;
                        if (SwooleHandler::exists('userWatchlistTable', $swtKey)) {
                            $fd = $swoole->wsTable->get('uid:' . $userWatchlistData->user_id);
                            if ($swoole->isEstablished($fd['value'])) {
                                $swoole->push($fd['value'], json_encode([
                                    'getEventData' => ['uid' => $event['uid']]
                                ]));
                            }
                        }
                    }
                }

                SwooleHandler::remove('eventHasMarketsTable', $key);
            }
        }
    }

    public static function getEventSectionRemoved($swoole)
    {
        $eventNoMarketIdsTable = $swoole->eventNoMarketIdsTable;
        foreach ($eventNoMarketIdsTable as $key => $event) {
            $userSelectedLeagues = UserSelectedLeague::getSelectedLeagueByAllUsers([
                'league_id' => $event['master_league_id'],
                'schedule'  => $event['schedule'],
                'sport_id'  => $event['sport_id']
            ]);

            if ($userSelectedLeagues->exists()) {
                foreach ($userSelectedLeagues->get() as $userSelectedLeague) {
                    $swtKey = 'userId:' . $userSelectedLeague->user_id . ':sId:' . $event['sport_id'] . ':lId:' . $event['master_league_id'] . ':schedule:' . $event['schedule'];
                    if (SwooleHandler::exists('userSelectedLeaguesTable', $swtKey)) {
                        $fd = $swoole->wsTable->get('uid:' . $userSelectedLeague->user_id);
                        if ($swoole->isEstablished($fd['value'])) {
                            $swoole->push($fd['value'], json_encode([
                                'getForRemovalSection' => [
                                    'uid'                     => $event['uid'],
                                    'odd_type'                => $event['odd_type'],
                                    'market_event_identifier' => $event['market_event_identifier'],
                                ]
                            ]));
                        }
                    }
                }
            }

            $userWatchlist = UserWatchlist::getByUid($event['uid']);
            if ($userWatchlist->exists()) {
                foreach ($userWatchlist->get() AS $row) {
                    $swtKey = "userWatchlist:" . $row->user_id . ":masterEventId:" . $row->master_event_id;
                    if (SwooleHandler::exists('userWatchlistTable', $swtKey)) {
                        $fd = $swoole->wsTable->get('uid:' . $row->user_id);
                        if ($swoole->isEstablished($fd['value'])) {
                            $swoole->push($fd['value'], json_encode([
                                'getForRemovalSection' => [
                                    'uid'                     => $event['uid'],
                                    'odd_type'                => $event['odd_type'],
                                    'market_event_identifier' => $event['market_event_identifier'],
                                ]
                            ]));
                        }
                    }
                }
            }

            SwooleHandler::remove('eventNoMarketIdsTable', $key);
        }
    }

    private static function getInActiveEvents($swoole)
    {
        $inactiveEventsTable        = SwooleHandler::table('inactiveEventsTable');
        $inactiveEvents = [];
        foreach ($inactiveEventsTable as $key => $data) {
            $inactiveEvents[] = json_decode($data['event'], true);
            SwooleHandler::remove('inactiveEventsTable', $key);
        }

        if (!empty($inactiveEvents)) {
            $wsTable = SwooleHandler::table('wsTable');
            foreach ($wsTable as $key => $row) {
                if (strpos($key, 'uid:') === 0 && $swoole->isEstablished($row['value'])) {
                    $swoole->push($row['value'], json_encode(['getForRemovalEvents' => $inactiveEvents]));
                }
            }
        }
    }
}
