<?php

namespace App\Processes;

use App\Handlers\ProducerHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;

class KafkaProduce implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;
    private static $producerHandler;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            $kafkaProducer = app('KafkaProducer');
            self::$producerHandler = new ProducerHandler($kafkaProducer);
            $kafkaTopic = env('KAFKA_SCRAPE_MINMAX_POSTFIX', '_req');
            $kafkaOrderTopic = env('KAFKA_SCRAPE_ORDER_TOPIC', 'BET-ODDS');

            if ($swoole->wsTable->exist('data2Swt')) {
                $topicTable = $swoole->topicTable;
                $ordersTable = $swoole->ordersTable;
                while (!self::$quit) {
                    foreach ($topicTable as $key => $topic) {
                        if (strpos($topic['topic_name'], 'min-max-') === 0) {
                            $memUID = substr($topic['topic_name'], strlen('min-max-'));

                            $eventMarkets = DB::table('event_markets as em')
                                ->join('master_event_market_links as meml', 'meml.event_market_id', 'em.id')
                                ->join('master_event_markets as mem', 'mem.master_event_market_unique_id',
                                    'meml.master_event_market_unique_id')
                                ->join('master_events as me', 'me.master_event_unique_id', 'mem.master_event_unique_id')
                                ->join('providers as p', 'p.id', 'em.provider_id')
                                ->where('mem.master_event_market_unique_id', $memUID)
                                ->select('em.bet_identifier, p.alias, me.sport_id')
                                ->distinct()
                                ->get();

                            foreach ($eventMarkets as $eventMarket) {
                                $requestId = Str::uuid();
                                $requestTs = self::milliseconds();

                                $payload = [
                                    'request_uid' => $requestId,
                                    'request_ts'  => $requestTs,
                                    'sub_command' => 'scrape',
                                    'command'     => 'minmax'
                                ];
                                $payload['data'] = [
                                    'provider'  => strtolower($eventMarket->alias),
                                    'market_id' => $eventMarket->bet_identifier,
                                    'sport'     => $eventMarket->sport_id
                                ];

                                self::pushToKafka($payload, $requestId, strtolower($eventMarket->alias) . $kafkaTopic);
                            }
                        }
                        
                        if (strpos($topic['topic_name'], 'order-') === 0) {
                            $orderId = substr($topic['topic_name'], strlen('order-'));
                            if ($ordersTable->count() > 0) {
                                foreach ($ordersTable as $orderKey => $order) {
                                    $requestId = Str::uuid();
                                    $requestTs = self::milliseconds();
                                
                                    $payload = [
                                        'request_uid' => $requestId,
                                        'request_ts'  => $requestTs,
                                        'sub_command' => 'scrape',
                                        'command'     => 'bet'
                                    ];
                                    
                                    $payload['data'] = [
                                        'actual_stake' => $order->actual_stake,
                                        'odds'         => $order->odds,
                                        'market_id'    => $order->market_id,
                                        'event_id'     => $order->event_id,
                                        'score'        => $order->score
                                    ];
                                    
                                    self::pushToKafka($payload, $requestId, $kafkaOrderTopic);
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }

    private static function milliseconds()
    {
        $mt = explode(' ', microtime());
        return bcadd($mt[1], $mt[0], 8);
    }

    private static function pushToKafka(array $message = [], string $key, string $kafkaTopic)
    {
        try {
            self::$producerHandler->setTopic($kafkaTopic)
                ->send($message, $key);
        } catch (Exception $e) {
            Log::critical(self::PUBLISH_ERROR_MESSAGE, [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }
}
