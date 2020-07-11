<?php

namespace App\Tasks;

use App\Models\EventsData;
use Hhxsv5\LaravelS\Swoole\Task\Task;
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
use Illuminate\Support\Facades\Redis;

class WsEventsTransformation extends Task
{
    protected $message;
    protected $userId;
    protected $masterLeagueName;
    protected $schedule;
    protected $additional;

    public function __construct($userId, $params, $additional = false)
    {
        $this->userId             = $userId;
        $this->masterLeagueName   = $params[1];
        $this->schedule           = $params[2];
        $this->additional         = $additional;
    }

    public function handle()
    {
        try {
            $server = app('swoole');

            $topicTable = $server->topicTable;
            $masterLeague  = MasterLeague::where('name', $this->masterLeagueName)->first();
            $gameDetails   = Game::getGameDetails($masterLeague->id, $this->schedule);
            $userId        = $this->userId;

            $data      = eventTransformation($gameDetails, $userId, $topicTable, 'socket');
            $eventData = array_values($data);
            if (!empty($eventData)) {
                Redis::set($this->schedule . '_' . $this->masterLeagueName, json_encode($eventData));
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
