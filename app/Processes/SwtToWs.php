<?php

namespace App\Processes;

use App\Jobs\WsEvents;
use App\Models\UserProviderConfiguration;
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
                while (!self::$quit) {
                    self::getUpdatedOdds($swoole);
                    self::getUpdatedPrice($swoole);
                    self::getAdditionalLeagues($swoole);
                    self::getForRemovallLeagues($swoole);
                    usleep(1000000);
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
        $userEvents           = $swoole->userEventsTable;
        $userEnabledProviders = [];
        foreach ($updatedEventsTable as $k => $r) {
            $updatedMarkets = json_decode($r['value']);
            if (!empty($updatedMarkets)) {
                foreach ($updatedMarkets as $updatedMarket) {
                    foreach ($topicTable as $topic) {
                        if (strpos($topic['topic_name'], 'market-id-' . $updatedMarket->market_id) === 0) {
                            if (!array_key_exists($topic['user_id'], $userEnabledProviders)) {
                                $userProviderIds                         = UserProviderConfiguration::getProviderIdList($topic['user_id']);
                                $userEnabledProviders[$topic['user_id']] = $userProviderIds;
                            }
                            if (in_array($updatedMarket->provider_id, $userEnabledProviders[$topic['user_id']])) {
                                $fd = $wsTable->get('uid:' . $topic['user_id']);
                                $swoole->push($fd['value'], json_encode(['getUpdatedOdds' => [$updatedMarket]]));
                                foreach($userEvents as $key => $row) {
                                    if($row['master_event_market_unique_id'] == $updatedMarket->market_id) {
                                        $userEvents[$key]['odds'] = $updatedMarket->odds;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                $updatedEventsTable->del($k);
            }
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
                        foreach ($updatedMarkets as $updatedMarket) {
                            $swoole->push($fd['value'], json_encode(['getUpdatedPrice' => $updatedMarket]));
                        }
                    }
                }
                $updatedEventsTable->del($k);
            }
        }
    }

    private static function getAdditionalLeagues($swoole)
    {
        $abbr                  = "add";
        $part                  = strtolower('ADDITIONAL');
        $swtKey                = "::LEAGUE_" . strtoupper($part);
        $topic                 = "getAdditionalLeagues";
        $getActionLeaguesTable = $swoole->getActionLeaguesTable;
        $wsTable               = $swoole->wsTable;

        foreach ($getActionLeaguesTable as $_key => $_row) {
            if (strpos($_key, $swtKey) > -1) {
                $data = json_decode($_row['value']);
                if (!empty($data)) {
                    foreach ($wsTable as $key => $row) {
                        if (strpos($key, 'fd:') === 0) {
                            $fd = $wsTable->get('uid:' . $row['value']);
                            $swoole->push($fd['value'], json_encode([$topic => $data->{$abbr}]));
                        }
                    }
                }
                $getActionLeaguesTable->del($_key);
            }
        }
    }

    private static function getForRemovallLeagues($swoole)
    {
        $abbr                  = "rmv";
        $part                  = strtolower('removal');
        $swtKey                = "::LEAGUE_" . strtoupper($part);
        $topic                 = "getForRemovalLeagues";
        $slTable               = $swoole->userSelectedLeaguesTable;
        $getActionLeaguesTable = $swoole->getActionLeaguesTable;
        $wsTable               = $swoole->wsTable;

        foreach ($getActionLeaguesTable as $_key => $_row) {
            if (strpos($_key, $swtKey) === 0) {
                $data = json_decode($_row['value']);
                if (!empty($data)) {
                    foreach ($wsTable as $key => $row) {
                        if (strpos($key, 'fd:') === 0) {
                            $fd = $wsTable->get('uid:' . $row['value']);
                            $swoole->push($fd['value'], json_encode([$topic => $data->{$abbr}]));

                            foreach ($slTable as $slKey => $slRow) {
                                foreach ($data->{$abbr} as $_abbr) {
                                    if ((strpos($slKey, 'userId:' . $row['value']) > -1) &&
                                        (strpos($slKey, ':schedule:' . $_abbr->schedule . ':uniqueId:') > -1)) {
                                        if ($slTable[$slKey]['league_name'] == $_abbr->name) {
                                            $slTable->del($slKey);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $getActionLeaguesTable->del($_key);
            }
        }
    }
}
