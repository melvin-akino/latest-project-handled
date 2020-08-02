<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\{
    Game,
    Timezones,
    UserConfiguration,
    UserProviderConfiguration};

class WsWatchlist implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function handle()
    {
        try {
            $server = app('swoole');
            $fd     = $server->wsTable->get('uid:' . $this->userId);

            $userId    = $this->userId;
            $topicTable = $server->topicTable;
            $gameDetails = Game::getWatchlistGameDetails($userId);
            $data = eventTransformation($gameDetails, $userId,  $topicTable, 'socket-watchlist');

            $watchlist = is_array($data) ? $data : [];
            $eventData = array_values($watchlist);

            if (!empty($eventData)) {
                $server->push($fd['value'], json_encode([
                    'getWatchlist' => $eventData
                ]));
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
