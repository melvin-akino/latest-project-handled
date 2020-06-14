<?php

namespace App\Jobs;

use Exception;
use App\Models\{
    Game,
    MasterLeague,
    Order,
    Timezones,
    UserConfiguration,
    Provider,
    UserProviderConfiguration
};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WsEvents implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $params)
    {
        $this->userId             = $userId;
        $this->master_league_name = $params[1];
        $this->schedule           = $params[2];
    }

    public function handle()
    {
        try {
            $server = app('swoole');
            $fd     = $server->wsTable->get('uid:' . $this->userId);

            $topicTable = $server->topicTable;

            $userProviders = UserProviderConfiguration::getProviderIdList($this->userId);

            $userBets     = Order::getOrdersByUserId($this->userId);
            $userProviderIds = UserProviderConfiguration::getProviderIdList($this->userId);
            $masterLeague = MasterLeague::where('name', $this->master_league_name)->first();
            $gameDetails  = Game::getGameDetails($masterLeague->id, $this->schedule);
            $userConfig    = getUserDefault($this->userId, 'sort-event')['default_sort'];

            $data          = [];
            $userId        = $this->userId;
            $userTz        = "Etc/UTC";
            $getUserConfig = UserConfiguration::getUserConfig($userId)
                                              ->where('type', 'timezone')
                                              ->first();

            if ($getUserConfig) {
                $userTz = Timezones::find($getUserConfig->value)->name;
            }
            $data = eventTransformation($gameDetails, $userConfig, $userTz, $userId, $userProviderIds, $topicTable, 'socket');
            $eventData = array_values($data);
            if (!empty($eventData)) {
                $server->push($fd['value'], json_encode([
                    'getEvents' => $eventData
                ]));
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
