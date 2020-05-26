<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\{DB, Log};
use App\Models\EventMarket;
use Exception;

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
        try {
            $server              = app('swoole');
            $topicTable          = $server->topicTable;
            $wsTable             = $server->wsTable;
            $minMaxRequestsTable = $server->minMaxRequestsTable;
            $minmaxMarketTable   = $server->minmaxMarketTable;
            $minmaxPayloadTable  = $server->minmaxPayloadTable;

            $eventMarket = EventMarket::getEventMarkeByMemUID($this->master_event_market_unique_id);

            if ($eventMarket) {
                $fd = $wsTable->get('uid:' . $this->userId);
                $server->push($fd['value'], json_encode([
                    'removeMinMax' => [
                        'status' => true
                    ]
                ]));

                foreach ($topicTable as $key => $topic) {
                    if ($topic['topic_name'] == 'min-max-' . $this->master_event_market_unique_id &&
                        $topic['user_id'] == $this->userId) {
                        $topicTable->del($key);
                        break;
                    }
                }

                $noSubscription = true;
                foreach ($topicTable as $key => $topic) {
                    if ($topic['topic_name'] == 'min-max-' . $this->master_event_market_unique_id) {
                        $noSubscription = false;
                        break;
                    }
                }
                if ($noSubscription) {
                    $minMaxRequestsTable->del('memUID:' . $this->master_event_market_unique_id);
                }
                $minmaxMarketTable->del('minmax-market:' . $eventMarket->bet_identifier);
                $minmaxPayloadTable->del('minmax-payload:' . $eventMarket->bet_identifier);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

    }
}
