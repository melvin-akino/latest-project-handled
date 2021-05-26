<?php

namespace App\Jobs;

use App\Facades\SwooleHandler;
use App\Models\EventMarket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
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
            $minmaxDataTable     = $server->minmaxDataTable;
            $minmaxMarketTable   = $server->minmaxMarketTable;
            $minmaxPayloadTable  = $server->minmaxPayloadTable;

            $eventMarket = EventMarket::getProviderEventMarketsByMemUID($this->master_event_market_unique_id);

            if ($eventMarket) {
                foreach ($minMaxRequestsTable as $minMaxReqkey => $minMaxRequest) {
                    if ($this->master_event_market_unique_id == $minMaxRequest['memUID']) {
                        foreach ($topicTable as $key => $topic) {
                            if ($topic['topic_name'] == 'min-max-' . $minMaxRequest['market_id'] && $topic['user_id'] == $this->userId) {
                                $topicTable->del($key);
                                break;
                            }
                        }

                        $noSubscription = true;
                        foreach ($topicTable as $key => $topic) {
                            if ($topic['topic_name'] == 'min-max-' . $minMaxRequest['market_id']) {
                                $noSubscription = false;
                                break;
                            }
                        }

                        SwooleHandler::decCtr('minMaxRequestsTable', $this->master_event_market_unique_id . ":" . strtolower($minMaxRequest['provider']));

                        if ($noSubscription) {
                            // $minmaxDataTable->del('minmax-market:' . $minMaxRequest['memUID']);
                            $minmaxDataTable->del('minmax-market:' . $minMaxRequest['market_id']);
                        }
                        $minmaxMarketTable->del('minmax-market:' . $minMaxRequest['market_id']);
                        $minmaxPayloadTable->del('minmax-payload:' . $minMaxRequest['market_id']);
                    }
                }

                $fd = $wsTable->get('uid:' . $this->userId);
                if ($server->isEstablished($fd['value'])) {
                    $server->push($fd['value'], json_encode([
                        'removeMinMax' => [
                            'status' => true
                        ]
                    ]));
                }

                $toLogs = [
                    "class"       => "WsRemoveMinMax",
                    "message"     => "MinMax Removed (min-max-" . $minMaxRequest['market_id'] . ")",
                    "module"      => "JOB",
                    "status_code" => 200,
                ];
                monitorLog('monitor_jobs', 'info', $toLogs);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
