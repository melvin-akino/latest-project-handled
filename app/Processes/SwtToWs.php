<?php

namespace App\Processes;

use App\Facades\SwooleHandler;
use App\Models\Game;
use App\Models\League;
use App\Models\SystemConfiguration;
use App\Models\UserProviderConfiguration;
use App\Models\UserSelectedLeague;
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
                    self::getUpdatedPrice($swoole);
                    if ($i % 30 == 0) {
                        self::getUpdatedLeagues();
                        self::getEventSectionRemoved($swoole);
                    }

                    if ($i % 5 == 0) {
                        self::getForBetBarRemoval($swoole);
                        self::getEventScored($swoole);
                        self::getEventScored($swoole);
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
        $updatedEventsTable   = $swoole->updatedEventsTable;
        $wsTable              = $swoole->wsTable;
        $topicTable           = $swoole->topicTable;
//        $eventsInfoTable      = $swoole->eventsInfoTable;
        $userEnabledProviders = [];
        foreach ($updatedEventsTable as $k => $r) {
            $updatedMarkets = json_decode($r['value']);

            $uid = substr($k, strlen('updatedEvents:'));

            if (!empty($updatedMarkets)) {
                foreach ($updatedMarkets as $updatedMarket) {
                    foreach ($topicTable as $topic) {
                        if (strpos($topic['topic_name'], 'market-id-' . $updatedMarket->market_id) === 0) {
                            if (!array_key_exists($topic['user_id'], $userEnabledProviders)) {
                                $userProviderIds                         = UserProviderConfiguration::getProviderIdList($topic['user_id']);
                                $userEnabledProviders[$topic['user_id']] = $userProviderIds;
                            }
                            $fd = $wsTable->get('uid:' . $topic['user_id']);
                            if (in_array($updatedMarket->provider_id, $userEnabledProviders[$topic['user_id']]) && $swoole->isEstablished($fd['value'])) {
                                $swoole->push($fd['value'], json_encode(['getUpdatedOdds' => [$updatedMarket]]));
                            }

                            if (SwooleHandler::exists('eventsInfoTable', 'eventsInfo:' . $uid)) {
                                $swoole->push($fd['value'], json_encode([ 'getEventsUpdate' => json_decode(SwooleHandler::getValue('eventsInfoTable', 'eventsInfo:' . $uid)['value'], true) ]));
                                SwooleHandler::remove('eventsInfoTable', 'eventsInfo:' . $uid);
                            }
                        }
                    }
                }
                foreach ($userEnabledProviders as $userId => $userEnabledProvider) {
                    $fd = $wsTable->get('uid:' . $userId);
                    if ($swoole->isEstablished($fd['value'])) {
                        $swoole->push($fd['value'], json_encode(['getEventHasOtherMarket' => [
                            'uid'              => $uid,
                            'has_other_markets' => Game::checkIfHasOtherMarkets($uid, $userEnabledProvider)
                        ]]));
                    }
                }
                $updatedEventsTable->del($k);
            }
        }
    }

    private static function getUpdatedLeagues()
    {
        if (SwooleHandler::exists('updateLeaguesTable', 'updateLeagues')) {
            SwooleHandler::remove('updateLeaguesTable', 'updateLeagues');
            wsEmit(['getUpdatedLeagues' => [
                'status' => true
            ]]);
        }
    }

    private static function getUpdatedPrice($swoole)
    {
        $updatedEventsTable = $swoole->updatedEventsTable;
        $wsTable            = $swoole->wsTable;
        $topicTable         = $swoole->topicTable;

        foreach ($updatedEventsTable as $k => $r) {
            $updatedMarkets = json_decode($r['value']);
            if (!empty($updatedMarkets)) {
                foreach ($topicTable as $topic => $_row) {
                    if (strpos($_row['topic_name'], 'min-max-') === 0) {
                        $userId = $_row['user_id'];
                        $fd     = $wsTable->get('uid:' . $userId);
                        if ($swoole->isEstablished($fd['value'])) {
                            foreach ($updatedMarkets as $updatedMarket) {
                                $swoole->push($fd['value'], json_encode(['getUpdatedPrice' => $updatedMarket]));
                            }
                        }
                    }
                }
                $updatedEventsTable->del($k);
            }
        }
    }

    private static function getForBetBarRemoval($swoole)
    {
        $topicTable = $swoole->topicTable;
        $userForRemovalBet = [];
        foreach ($topicTable as $key => $topic) {
            if (strpos($topic['topic_name'], 'removal-bet-') === 0) {
                $userForRemovalBet[$topic['user_id']] = true;
                SwooleHandler::remove('topicTable', $key);
            }
        }

        if (!empty($userForRemovalBet)) {
            foreach ($userForRemovalBet as $userId => $bet) {
                $fd = $swoole->wsTable->get('uid:' . $userId);
                if ($swoole->isEstablished($fd['value'])) {
                    $swoole->push($fd['value'], json_encode([
                        'forBetBarRemoval' => ['status' => true]
                    ]));
                }
            }
        }
    }

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
            SwooleHandler::remove('eventsScoredTable', $key);
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
                                    'uid' => $event['uid'],
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
}
