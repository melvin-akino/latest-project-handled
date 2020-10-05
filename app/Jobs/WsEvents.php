<?php

namespace App\Jobs;

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
        try {
            $userId       = $this->userId;
            $server       = app('swoole');
            $fd           = $server->wsTable->get('uid:' . $userId);
            $topicTable   = $server->topicTable;
            $masterLeague = MasterLeague::where('name', $this->masterLeagueName)->first();

            if (count($this->params) > 3) {
                $meUID       = $this->params[3];
                $gameDetails = Game::getGameDetails($masterLeague->id, $this->schedule, $userId, $meUID);
                if (count($this->params) > 4) {
                    $otherTransformed   = Game::getOtherMarketsByMemUID($meUID);
                    $otherMarketDetails = [
                        'meUID'       => $meUID,
                        'transformed' => $otherTransformed
                    ];
                    $data               = eventTransformation($gameDetails, $userId, $topicTable, 'socket', $otherMarketDetails);
                } else {
                    $data = eventTransformation($gameDetails, $userId, $topicTable, 'socket');
                }
            } else {
                $gameDetails = Game::getGameDetails($masterLeague->id, $this->schedule, $userId);
                $data        = eventTransformation($gameDetails, $userId, $topicTable, 'socket');
            }

            $gameData  = is_array($data) ? $data : [];
            $eventData = array_values($gameData);

            if (!empty($eventData)) {
                $channelName = $this->additional ? "getAdditionalEvents" : "getEvents";

                if ($server->isEstablished($fd['value'])) {
                    $server->push($fd['value'], json_encode([
                        $channelName => $eventData
                    ]));
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
