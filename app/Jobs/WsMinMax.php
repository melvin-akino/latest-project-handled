<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
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
            $minMaxCachesTable   = $swoole->minMaxCachesTable;
            $wsTable             = $swoole->wsTable;

            $eventMarkets = EventMarket::getEventMarketByMemUID($this->master_event_market_unique_id);

            if ($eventMarkets) {
                foreach ($eventMarkets as $eventMarket) {
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
                        'memUID'    => $this->master_event_market_unique_id
                    ];
                    $minMaxRequestsTable->set('mId:' . $eventMarket->bet_identifier . ':memUID:' . $this->master_event_market_unique_id, $minMaxRequestsPayload);

                    $debug = env('DEBUG_SEND', false);
                    if($debug) {
                        SendLogData::MinMax('requestminmax', json_encode($minMaxRequestsPayload));
                    }

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
                    KafkaPush::dispatch(strtolower($eventMarket->alias) . '_minmax_req', $payload, $requestId);

                    $doesExist = false;
                    foreach ($minMaxCachesTable as $k => $v) {
                        if ($k == 'marketId:' . $eventMarket->bet_identifier) {
                            $doesExist = true;
                            break;
                        }
                    }
                    if ($doesExist) {
                        $minmaxCache = $minMaxCachesTable->get('marketId:' . $eventMarket->bet_identifier);
                        $fd          = $wsTable->get('uid:' . $this->userId)['value'];
                        $swoole->push($fd, json_encode([
                            'getMinMax' => json_decode($minmaxCache['value'], true)
                        ]));
                    }
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
