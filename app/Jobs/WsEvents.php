<?php

namespace App\Jobs;

use App\Facades\SwooleHandler;
use Exception;
use App\Models\{
    Game,
    MasterLeague
};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class WsEvents implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $params, $additional = false)
    {
        $this->userId           = $userId;
        $this->params           = $params;
        $this->masterLeagueName = $params[1];
        $this->schedule         = $params[2];
        $this->additional       = $additional;
    }

    public function handle()
    {
        $channelName = $this->additional ? "getAdditionalEvents" : "getEvents";
        $server      = app('swoole');
        $eventData   = [];
        $userId      = $this->userId;
        $fd          = SwooleHandler::getValue('wsTable', 'uid:' . $userId);
        try {
            $topicTable   = SwooleHandler::table('topicTable');
            $masterLeague = MasterLeague::getLeagueNameDetails($this->masterLeagueName)->first();

            if (count($this->params) > 3) {
                $meUID       = $this->params[3];
                $singleEvent = true;
                $gameDetails = Game::getGameDetails($masterLeague->master_league_id, $this->schedule, $userId, $meUID);
                if (count($this->params) > 4) {
                    $otherTransformed   = Game::getOtherMarketsByMasterEventId($meUID);
                    $otherMarketDetails = [
                        'meUID'       => $meUID,
                        'transformed' => $otherTransformed
                    ];
                    $data               = eventTransformation($gameDetails, $userId, $topicTable, 'socket', $otherMarketDetails, $singleEvent);
                } else {
                    $data = eventTransformation($gameDetails, $userId, $topicTable, 'socket', [], $singleEvent);
                }
            } else {
                $gameDetails = Game::getGameDetails($masterLeague->master_league_id, $this->schedule, $userId);
                $data        = eventTransformation($gameDetails, $userId, $topicTable, 'socket');
            }

            $gameData  = is_array($data) ? $data : [];
            $eventData = array_values($gameData);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "WsEvents",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage() . " | " . $e->getFile(),
                "module"      => "JOB_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_jobs', 'error', $toLogs);
        } finally {
            if ($server->isEstablished($fd['value'])) {
                if (count($this->params) > 3) {
                    $payload = [
                        'leagueName' => $this->masterLeagueName,
                        'schedule'   => $this->schedule,
                        'uid'        => $this->params[3]
                    ];
                } else {
                    $payload = [
                        'leagueName' => $this->masterLeagueName,
                        'schedule'   => $this->schedule
                    ];
                }

                $server->push($fd['value'], json_encode([
                    $channelName => !empty($eventData) ? $eventData : $payload
                ]));

                $toLogs = [
                    "class"       => "WsEvents",
                    "message"     => $payload,
                    "module"      => "JOB",
                    "status_code" => 200,
                ];
                monitorLog('monitor_jobs', 'info', $toLogs);
            }
        }
    }
}
