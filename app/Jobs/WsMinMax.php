<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\{DB, Log};
use Exception;
use Illuminate\Support\Str;

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
            $topicTable          = app('swoole')->topicTable;
            $minMaxRequestsTable = app('swoole')->minMaxRequestsTable;
            $doesExist           = false;
            foreach ($topicTable as $topic) {
                if ($topic['topic_name'] == 'min-max-' . $this->master_event_market_unique_id &&
                    $topic['user_id'] == $this->userId) {
                    $doesExist = true;
                    break;
                }
            }
            if (empty($doesExist)) {
                $topicTable->set('userId:' . $this->userId . ':unique:' . uniqid(), [
                    'user_id'    => $this->userId,
                    'topic_name' => 'min-max-' . $this->master_event_market_unique_id
                ]);
            }

            $eventMarket = DB::table('event_markets as em')
                ->join('master_event_market_links as meml', 'meml.event_market_id', 'em.id')
                ->join('master_event_markets as mem', 'mem.master_event_market_unique_id',
                    'meml.master_event_market_unique_id')
                ->join('master_events as me', 'me.master_event_unique_id', 'mem.master_event_unique_id')
                ->join('providers as p', 'p.id', 'em.provider_id')
                ->where('mem.master_event_market_unique_id', $this->master_event_market_unique_id)
                ->select('em.bet_identifier', 'p.alias', 'me.sport_id', 'me.game_schedule')
                ->distinct()
                ->first();

            if ($eventMarket) {
                $minMaxRequestsTable->set('memUID:' . $this->master_event_market_unique_id, [
                    'provider'  => strtolower($eventMarket->alias),
                    'market_id' => $eventMarket->bet_identifier,
                    'sport'     => $eventMarket->sport_id,
                    'schedule'  => $eventMarket->game_schedule,
                ]);

                $requestId = (string)Str::uuid();
                $requestTs = $this->milliseconds();

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
                    'schedule'  => $eventMarket->game_schedule,
                ];

                KafkaPush::dispatch(strtolower($eventMarket->alias) . '_minmax_req', $payload, $requestId);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    private function milliseconds()
    {
        $mt = explode(' ', microtime());
        return bcadd($mt[1], $mt[0], 8);
    }
}
