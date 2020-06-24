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
                }
            }
            $eventScheduleChangeTable->del($k);
        }
    }

    public static function getAdditionalEvents($swoole)
    {
        $additionalEventsTable = $swoole->additionalEventsTable;
        $wsTable               = $swoole->wsTable;
        foreach ($additionalEventsTable as $k => $r) {
            $additionalEvents = json_decode($r['value']);
            if (!empty($additionalEvents)) {
                $sportId      = $additionalEvents->sport_id;
                $gameSchedule = $additionalEvents->schedule;
                $leagueName   = $additionalEvents->league_name;

                $userSelectedLeaguesTable = $swoole->userSelectedLeaguesTable;
                foreach ($userSelectedLeaguesTable as $uslKey => $uslData) {
                    $userId       = $uslData['user_id'];
                    $defaultSport = getUserDefault($userId, 'sport');
                    if ((int) $defaultSport['default_sport'] == $sportId) {
                        if ($sportId == $uslData['sport_id'] && $gameSchedule == $uslData['schedule'] && $uslData['league_name'] == $leagueName
                        ) {
                            WsEvents::dispatch($userId, [1 => $uslData['league_name'], 2 => $gameSchedule], true);
                        }
                    }
                }
                $additionalEventsTable->del($k);
            }
        }
    }

    private static function getUpdatedOdds($swoole)
    {
        $updatedEventsTable = $swoole->updatedEventsTable;
        $wsTable            = $swoole->wsTable;
        $topicTable         = $swoole->topicTable;
        foreach ($updatedEventsTable as $k => $r) {
            $updatedMarkets = json_decode($r['value']);
            if (!empty($updatedMarkets)) {
                foreach ($topicTable as $topic) {
                    if (strpos($topic['topic_name'], 'market-id-') === 0) {
                        $fd = $wsTable->get('uid:' . $topic['user_id']);
                        $swoole->push($fd['value'], json_encode(['getUpdatedOdds' => $updatedMarkets]));
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
                foreach ($topicTable AS $topic => $_row) {
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

        foreach ($getActionLeaguesTable AS $_key => $_row) {
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

        foreach ($getActionLeaguesTable AS $_key => $_row) {
            if (strpos($_key, $swtKey) === 0) {
                $data = json_decode($_row['value']);
                if (!empty($data)) {
                    foreach ($wsTable as $key => $row) {
                        if (strpos($key, 'fd:') === 0) {
                            $fd = $wsTable->get('uid:' . $row['value']);
                            $swoole->push($fd['value'], json_encode([$topic => $data->{$abbr}]));

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
                $getActionLeaguesTable->del($_key);
            }
        }
    }
}
