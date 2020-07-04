<?php

namespace App\Jobs;

use Exception;
use App\Models\{
    Game,
    MasterLeague,
    Timezones,
    UserConfiguration,
    UserProviderConfiguration
};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use App\Tasks\WsEventsTransformation;
use Illuminate\Support\Facades\Redis;

class WsEvents implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $params, $additional = false)
    {
        $this->userId             = $userId;
        $this->params             = $params;
        $this->masterLeagueName   = $params[1];
        $this->schedule           = $params[2];
        $this->additional         = $additional;
    }

    public function handle()
    {
        try {
            $server = app('swoole');
            $fd     = $server->wsTable->get('uid:' . $this->userId);
            // Task::deliver(new WsEventsTransformation($this->userId, $this->params, $this->additional));
            // if (Redis::get($this->schedule . '_' . $this->masterLeagueName)) {
            //     $data = json_decode(Redis::get($this->schedule . '_' . $this->masterLeagueName), false);
            // } else {
                $userId = $this->userId;
                $topicTable = $server->topicTable;
                $userEvents = $server->userEventsTable;


                $userProviderIds = UserProviderConfiguration::getProviderIdList($this->userId);
                $userConfig      = getUserDefault($this->userId, 'sort-event')['default_sort'];
                $masterLeague    = MasterLeague::where('name', $this->masterLeagueName)->first();
                $userTz        = "Etc/UTC";
                $getUserConfig = UserConfiguration::getUserConfig($userId)
                                                    ->where('type', 'timezone')
                                                    ->first();

                if ($getUserConfig) {
                    $userTz = Timezones::find($getUserConfig->value)->name;
                }

                $gameDetails = Game::getGameDetails($masterLeague->id, $this->schedule);
                foreach($gameDetails as $data) {
                    $swtKey = 'selected:u:' . $userId . ':l:' . $data->league_id . ':s:' . $data->game_schedule . ':e:' . $data->master_event_id  .  ':o:' . $data->odd_type_id . ':t:' . $data->market_flag;
                    foreach($data as $key => $value) {
                        $userEvents->set($swtKey, [$key => $value]);
                    }
                }

                $data = eventTransformation($gameDetails, $userConfig, $userTz, $userId, $userProviderIds, $topicTable, 'socket');
            // }
            $gameData = is_array($data) ? $data : [];
            $eventData = array_values($gameData);

            if (!empty($eventData)) {
                $channelName = $this->additional ? "getAdditionalEvents" : "getEvents";

                $server->push($fd['value'], json_encode([
                    $channelName => $eventData
                ]));
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
