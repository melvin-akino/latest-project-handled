<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WsEvents implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $params)
    {
        $this->userId = $userId;
        $this->master_league_name = $params[1];
    }

    public function handle()
    {
        $server = app('swoole');
        $fd = $server->wsTable->get('uid:' . $this->userId);

        $getEvents = [];
        $providerPriority = 0;
        $providerId = 0;

        $providersTable = $server->providersTable;
        $eventsTable = $server->eventsTable;
        foreach ($providersTable as $key => $provider) {
            if (empty($providerPriority) || $providerPriority > $provider['priority']) {
                $providerPriority = $provider['priority'];
                $providerId = $provider['id'];
            }
        }
        foreach ($eventsTable as $key => $event) {
            if ($event['master_league_name'] == $this->master_league_name) {
                $transformed = $server->transformedTable;
                if ($transformed->exist('uid:' . $event['master_event_unique_id'] . ":pId:" . $providerId)) {
                    $getEvents[] = json_decode($transformed->get('uid:' . $event['master_event_unique_id'] . ":pId:" . $providerId)['value'],
                        true);
                }
            }
        }

        $server->push($fd['value'], json_encode([
            'getEvents' => $getEvents
        ]));
    }
}
