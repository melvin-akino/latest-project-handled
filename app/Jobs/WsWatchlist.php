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
        $server = app('swoole');
        $fd     = $server->wsTable->get('uid:' . $this->userId);

        $watchlist = [];

        $providerPriority = 0;
        $providerId       = 0;

        $providersTable = $server->providersTable;
        foreach ($providersTable as $key => $provider) {
            if (empty($providerPriority) || $providerPriority > $provider['priority']) {
                $providerPriority = $provider['priority'];
                $providerId       = $provider['id'];
            }
        }

        $transformed = $server->transformedTable;
        // Id format for watchlistTable = 'userWatchlist:' . $userId . ':league:' . $league
        $userWatchlistTable = $server->userWatchlistTable;
        foreach ($userWatchlistTable as $key => $row) {
            $uid = substr($key, strlen('userWatchlist:' . $this->userId . ':masterEventUniqueId:'));

            if ($transformed->exist('uid:' . $uid . ":pId:" . $providerId)) {
                $watchlist[] = json_decode($transformed->get('uid:' . $uid . ":pId:" . $providerId)['value'],
                    true);;
            }
        }

        $userId = $this->userId;
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
                if (strpos($key, 'selected:u:' . $userId . ':l:' . $data->league_id . ':s:' . $data->game_schedule) !== false) {
                        $userEvents->del($key);
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
    }
}
