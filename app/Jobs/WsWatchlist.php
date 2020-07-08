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
            $userEvents = $server->userEventsTable;

            $userProviderIds = UserProviderConfiguration::getProviderIdList($this->userId);
            $userConfig      = getUserDefault($this->userId, 'sort-event')['default_sort'];
            $userTz          = "Etc/UTC";
            $getUserConfig   = UserConfiguration::getUserConfig($userId)
                                            ->where('type', 'timezone')
                                            ->first();

            if ($getUserConfig) {
                $userTz = Timezones::find($getUserConfig->value)->name;
            }

            $gameDetails = Game::getWatchlistGameDetails($this->userId);

            foreach($gameDetails as $data) {
                foreach ($userEvents as $key => $row) {
                    if (strpos($key, 'selected:u:' . $userId . ':l:' . $data->league_id . ':s:' . $data->game_schedule . ':e:' . $data->master_event_id . ':') !== false) {
                        $userEvents->del($key);
                    } else {
                        continue;
                    }
                }

                $swtKey = 'watchlist:u:' . $userId . ':e:' . $data->master_event_id  .  ':o:' . $data->odd_type_id . ':t:' . $data->market_flag;
                foreach($data as $key => $value) {
                    $userEvents->set($swtKey, [$key => $value]);
                }
            }


            $data = eventTransformation($gameDetails, $userConfig, $userTz, $userId, $userProviderIds, $topicTable, 'socket');

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
