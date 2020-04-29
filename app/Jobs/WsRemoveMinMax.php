<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class WsRemoveMinMax implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $params)
    {
        $this->userId                        = $userId;
        $this->master_event_market_unique_id = $params[1];
    }

    public function handle()
    {
        $server      = app('swoole');
        $topicTable  = $server->topicTable;
        $wsTable     = $server->wsTable;
        $minMaxRequestsTable = app('swoole')->minMaxRequestsTable;

        $eventMarket = DB::table('event_markets as em')
            ->join('master_event_market_links as meml', 'meml.event_market_id', 'em.id')
            ->join('master_event_markets as mem', 'mem.master_event_market_unique_id',
                'meml.master_event_market_unique_id')
            ->join('master_events as me', 'me.master_event_unique_id', 'mem.master_event_unique_id')
            ->join('providers as p', 'p.id', 'em.provider_id')
            ->where('mem.master_event_market_unique_id', $this->master_event_market_unique_id)
            ->select('em.bet_identifier', 'p.alias', 'me.sport_id')
            ->distinct()
            ->first();

        if ($eventMarket) {
            $fd = $wsTable->get('uid:' . $this->userId);
            $server->push($fd['value'], json_encode([
                'removeMinMax' => [
                    'status' => true
                ]
            ]));

            foreach($topicTable as $key => $topic) {
                if ($topic['topic_name'] == 'min-max-' . $this->master_event_market_unique_id &&
                    $topic['user_id'] == $this->userId) {
                    $topicTable->del($key);
                    break;
                }
            }

            $noSubscription = true;
            foreach($topicTable as $key => $topic) {
                if ($topic['topic_name'] == 'min-max-' . $this->master_event_market_unique_id) {
                    $noSubscription = false;
                    break;
                }
            }
            if ($noSubscription) {
                $minMaxRequestsTable->del('memUID:' . $this->master_event_market_unique_id);
                $wsTable->del('minmax-market:' . $this->master_event_market_unique_id);
                $wsTable->del('minmax-payload:' . $this->master_event_market_unique_id);
            }
        }
    }
}
