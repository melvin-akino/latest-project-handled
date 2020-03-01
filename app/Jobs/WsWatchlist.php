<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Str;

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
        $fd = $server->wsTable->get('uid:' . $this->userId);

        $watchlist = [];

        $providerPriority = 0;
        $providerId = 0;

        $providersTable = $server->providersTable;
        foreach ($providersTable as $key => $provider) {
            if (empty($providerPriority) || $providerPriority > $provider['priority']) {
                $providerPriority = $provider['priority'];
                $providerId = $provider['id'];
            }
        }

        $transformed = $server->transformedTable;
        // Id format for watchlistTable = 'userWatchlist:' . $userId . ':league:' . $league
        $wsTable = $server->wsTable;
        foreach ($wsTable as $key => $row) {
            if (strpos($key, 'userWatchlist:' . $this->userId . ':masterEventUniqueId:') === 0) {
                $uid = substr($key, strlen('userWatchlist:' . $this->userId . ':masterEventUniqueId:'));

                if ($transformed->exist('uid:' . $uid . ":pId:" . $providerId)) {
                    $watchlist[] = json_decode($transformed->get('uid:' . $uid . ":pId:" . $providerId)['value'],
                        true);;
                }
            }
        }

        $server->push($fd['value'], json_encode([
            'getWatchlist' => $watchlist
        ]));
    }
}
