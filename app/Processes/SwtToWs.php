<?php

namespace App\Processes;

use App\Jobs\WsEvents;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Hhxsv5\LaravelS\Swoole\Task\Task;
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
                    self::getAdditionalEvents($swoole);
                    self::getUpdatedEventsSchedule($swoole);
                    self::getUpdatedOdds($swoole);
                    self::getUpdatedPrice($swoole);
                    self::getAdditionalLeagues($swoole);
                    self::getForRemovallLeagues($swoole);
                    usleep(1000);
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

    public static function getUpdatedEventsSchedule($swoole)
    {
        $eventScheduleChangeTable = $swoole->eventScheduleChangeTable;
        $wsTable                  = $swoole->wsTable;
        foreach ($eventScheduleChangeTable as $k => $r) {
            $updatedEventSchedule = json_decode($r['value']);
            foreach ($wsTable as $key => $row) {
                if (strpos($key, 'fd:') === 0) {
                    $fd = $wsTable->get('uid:' . $row['value']);
                    $swoole->push($fd['value'], json_encode(['getUpdatedEventsSchedule' => $updatedEventSchedule]));
                    $eventScheduleChangeTable->del($k);
                }
            }
        }
    }

    public static function getAdditionalEvents($swoole)
    {
        $additionalEventsTable = $swoole->additionalEventsTable;
        $wsTable               = $swoole->wsTable;
        foreach ($additionalEventsTable as $k => $r) {
            $additionalEvents = json_decode($r['value']);
            if (!empty($additionalEvents)) {
                foreach ($wsTable as $key => $row) {
                    if (strpos($key, 'fd:') === 0) {
                        $userId       = $row['value'];
                        $sportId      = $additionalEvents->sport_id;
                        $gameSchedule = $additionalEvents->schedule;
                        $defaultSport = getUserDefault($userId, 'sport');
                        if ((int)$defaultSport['default_sport'] == $sportId) {
                            $userSelectedLeaguesTable = $swoole->userSelectedLeaguesTable;
                            foreach ($userSelectedLeaguesTable as $uslKey => $uslData) {
                                if (strpos($uslKey, 'userId:' . $userId . ':sId:' . $sportId) === 0) {
                                    WsEvents::dispatchNow($userId, [1 => $uslData['league_name'], 2 => $gameSchedule]);
                                }
                            }
                        }
                        $wsTable->del($k);
                    }
                }
            }
        }
    }

    private static function getUpdatedOdds($swoole)
    {
        $updatedEventsTable = $swoole->updatedEventsTable;
        $wsTable            = $swoole->wsTable;
        $topicTable         = $swoole->topicTable;
        foreach ($updatedEventsTable as $k => $r) {
            foreach ($wsTable as $key => $row) {
                $updatedMarkets = json_decode($r['value']);
                if (!empty($updatedMarkets)) {
                    if (strpos($key, 'fd:') === 0) {
                        foreach ($topicTable as $topic) {
                            if (strpos($topic['topic_name'], 'market-id-') === 0) {
                                if ($topic['user_id'] == $row['value']) {
                                    $fd = $wsTable->get('uid:' . $row['value']);
                                    $swoole->push($fd['value'], json_encode(['getUpdatedOdds' => $updatedMarkets]));
                                    $updatedEventsTable->del($k);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private static function getUpdatedPrice($swoole)
    {
        $updatedEventsTable = $swoole->updatedEventsTable;
        $wsTable            = $swoole->wsTable;
        $topicTable         = $swoole->topicTable;

        foreach ($updatedEventsTable as $k => $r) {
            foreach ($wsTable as $key => $row) {
                $updatedMarkets = json_decode($r['value']);
                if (!empty($updatedMarkets)) {
                    foreach ($topicTable AS $topic => $_row) {
                        if (strpos($_row['topic_name'], 'min-max-') === 0) {
                            $userId = $_row['user_id'];
                            $fd     = $wsTable->get('uid:' . $userId);
                            foreach ($updatedMarkets as $updatedMarket) {
                                $swoole->push($fd['value'], json_encode(['getUpdatedPrice' => $updatedMarket]));
                            }
                            $updatedEventsTable->del($k);
                            break;
                        }
                    }
                }
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

        foreach ($getActionLeaguesTable AS $_key => $_row) {
            if (strpos($_key, $swtKey) > -1) {
                $data = json_decode($getActionLeaguesTable[$_key]['value']);
                foreach ($wsTable as $key => $row) {
                    if (strpos($key, 'fd:') === 0) {
                        $fd = $wsTable->get('uid:' . $row['value']);
                        if (!empty($data)) {
                            $swoole->push($fd['value'], json_encode([$topic => $data->{$abbr}]));
                            $getActionLeaguesTable->del($_key);
                        }
                    }
                }
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

        foreach ($getActionLeaguesTable AS $_key => $_row) {
            if (strpos($_key, $swtKey) === 0) {
                $data = json_decode($getActionLeaguesTable[$_key]['value']);
                foreach ($wsTable as $key => $row) {
                    if (strpos($key, 'fd:') === 0) {
                        $fd = $wsTable->get('uid:' . $row['value']);
                        if (!empty($data)) {
                            $swoole->push($fd['value'], json_encode([$topic => $data->{$abbr}]));
                            $getActionLeaguesTable->del($_key);

                            foreach ($slTable AS $slKey => $slRow) {
                                foreach ($data->{$abbr} AS $_abbr) {
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
            }
        }
    }
}
