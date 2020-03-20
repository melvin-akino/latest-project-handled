<?php

namespace App\Processes;

use App\Handlers\ProducerHandler;
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
            $kafkaTopic = env('KAFKA_SCRAPE_MINMAX_REQUEST_POSTFIX', '_minmax_req');
            $kafkaOrderTopic = env('KAFKA_SCRAPE_ORDER_REQUEST_POSTFIX', '_bet_req');

            if ($swoole->wsTable->exist('data2Swt')) {
                $topicTable = $swoole->topicTable;
                $minMaxRequestsTable = $swoole->minMaxRequestsTable;
                $ordersTable = $swoole->ordersTable;

                while (!self::$quit) {
                    foreach ($topicTable as $key => $topic) {
                        if (strpos($topic['topic_name'], 'min-max-') === 0) {
                            $memUID = substr($topic['topic_name'], strlen('min-max-'));

                            foreach ($minMaxRequestsTable as $minMaxRequest) {
                                $requestId = Str::uuid();
                                $requestTs = self::milliseconds();

                                $payload = [
                                    'request_uid' => $requestId,
                                    'request_ts'  => $requestTs,
                                    'sub_command' => 'scrape',
                                    'command'     => 'minmax'
                                ];
                                $payload['data'] = $minMaxRequest;
                                self::pushToKafka($payload, $requestId, strtolower($minMaxRequest['provider']) . $kafkaTopic);
                            }
                        }

                        if (strpos($topic['topic_name'], 'order-') === 0) {
                            $orderId = substr($topic['topic_name'], strlen('order-'));
                            if ($ordersTable->count() > 0) {
                                foreach ($ordersTable as $orderKey => $order) {
                                    $order     = (object) $order;
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
