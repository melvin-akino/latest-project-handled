<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\{
    Game,
    MasterEvent
};

class WsWatchlist implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $params = null)
    {
        $this->userId = $userId;
        $this->params = $params;
    }

    public function handle()
    {
        $eventData = [];
        try {
            $server = app('swoole');
            $fd     = $server->wsTable->get('uid:' . $this->userId);

            $userId     = $this->userId;
            $topicTable = $server->topicTable;
            $params     = $this->params;

            if ($params) {
                $data = [];
                $eventUID    = $params[1];
                $masterEvent = MasterEvent::where('master_event_unique_id', $eventUID)->first();
                if ($masterEvent) {
                    $gameDetails = Game::getWatchlistGameDetails($userId, $masterEvent->id);
                    $singleEvent = true;

                    if (count($params) > 2) {
                        $otherTransformed   = Game::getOtherMarketsByMasterEventId($eventUID);
                        $otherMarketDetails = [
                            'meUID'       => $eventUID,
                            'transformed' => $otherTransformed
                        ];
                        $data               = eventTransformation($gameDetails, $userId, $topicTable, 'socket-watchlist', $otherMarketDetails, $singleEvent);
                    } else {
                        $data = eventTransformation($gameDetails, $userId, $topicTable, 'socket-watchlist', [], $singleEvent);
                    }
                }
            } else {
                $gameDetails = Game::getWatchlistGameDetails($userId);
                $data        = eventTransformation($gameDetails, $userId, $topicTable, 'socket-watchlist');
            }

            $watchlist = is_array($data) ? $data : [];
            $eventData = array_values($watchlist);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "WsWatchlist",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "JOB_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_jobs', 'error', $toLogs);
        } finally {
            if ($server->isEstablished($fd['value'])) {
                $server->push($fd['value'], json_encode([
                    'getWatchlist' => $eventData
                ]));

                $toLogs = [
                    "class"       => "WsWatchlist",
                    "message"     => [
                        'getWatchlist' => $eventData
                    ],
                    "module"      => "JOB",
                    "status_code" => 200,
                ];
                monitorLog('monitor_jobs', 'error', $toLogs);
            }
        }
    }
}
