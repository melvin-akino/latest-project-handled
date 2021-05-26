<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Facades\SwooleHandler;
use App\Models\EventMarket;
use Exception;
use Illuminate\Support\Str;
use PrometheusMatric;
use SendLogData;

class WsMinMax implements ShouldQueue
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
            $swoole              = app('swoole');
            $topicTable          = $swoole->topicTable;
            $minMaxRequestsTable = $swoole->minMaxRequestsTable;

            $eventMarkets = EventMarket::getEventMarketByMemUID($this->master_event_market_unique_id);

            if ($eventMarkets) {
                foreach ($eventMarkets AS $eventMarket) {
                    $doesExist = false;
                    foreach ($topicTable as $topic) {
                        if ($topic['topic_name'] == 'min-max-' . $eventMarket->bet_identifier &&
                            $topic['user_id'] == $this->userId) {
                            $doesExist = true;
                            break;
                        }
                    }

                    if (!$doesExist) {
                        $topicTable->set('userId:' . $this->userId . ':unique:' . uniqid(), [
                            'user_id'    => $this->userId,
                            'topic_name' => 'min-max-' . $eventMarket->bet_identifier
                        ]);
                    }

                    $minMaxRequestsPayload =  [
                        'provider'  => strtolower($eventMarket->alias),
                        'market_id' => $eventMarket->bet_identifier,
                        'sport'     => $eventMarket->sport_id,
                        'schedule'  => $eventMarket->game_schedule,
                        'event_id'  => $eventMarket->event_identifier,
                        'odds'      => $eventMarket->odds,
                        'memUID'    => $this->master_event_market_unique_id,
                        'counter'   => 1,
                    ];

                    if (!$minMaxRequestsTable->exists($this->master_event_market_unique_id . ":" . strtolower($eventMarket->alias))) {
                        $minMaxRequestsTable->set($this->master_event_market_unique_id . ":" . strtolower($eventMarket->alias), $minMaxRequestsPayload);
                    } else if (!$doesExist) {
                        SwooleHandler::incCtr('minMaxRequestsTable', $this->master_event_market_unique_id . ":" . strtolower($eventMarket->alias));
                    }

                    SendLogData::MinMax('requestminmax', json_encode($minMaxRequestsPayload));

                    PrometheusMatric::MakeMatrix('swoole_table_total', 'Swoole minMaxRequestsTable total ', 'minMaxRequestsTable');

                    $requestId = (string) Str::uuid();
                    $requestTs = getMilliseconds();

                    $payload         = [
                        'request_uid' => $requestId,
                        'request_ts'  => $requestTs,
                        'sub_command' => 'scrape',
                        'command'     => 'minmax'
                    ];
                    $payload['data'] = [
                        'provider'  => strtolower($eventMarket->alias),
                        'market_id' => $eventMarket->bet_identifier,
                        'sport'     => $eventMarket->sport_id,
                        'event_id'  => (string) $eventMarket->event_identifier,
                        'schedule'  => $eventMarket->game_schedule,
                        'odds'      => $eventMarket->odds
                    ];

                    Log::info('Min Max Initial Request');

                    $toLogs = [
                        "class"       => "WsMinMax",
                        "message"     => $payload,
                        "module"      => "JOB",
                        "status_code" => 200,
                    ];
                    monitorLog('monitor_jobs', 'info', $toLogs);

                    KafkaPush::dispatch(strtolower($eventMarket->alias) . '_minmax_req', $payload, $requestId);
                }
            }
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "WsMinMax",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "JOB_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_jobs', 'error', $toLogs);
        }
    }
}
