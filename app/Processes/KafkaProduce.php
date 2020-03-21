<?php

namespace App\Processes;

use App\Handlers\ProducerHandler;
use Illuminate\Support\Str;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;
use Carbon\Carbon;

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
            $kafkaProducer         = app('KafkaProducer');
            self::$producerHandler = new ProducerHandler($kafkaProducer);

            $kafkaTopics = [
                'req_minmax'       => env('KAFKA_SCRAPE_MINMAX_REQUEST_POSTFIX', '_minmax_req'),
                'req_order'        => env('KAFKA_SCRAPE_ORDER_REQUEST_POSTFIX', '_bet_req'),
                'req_open_order'   => env('KAFKA_SCRAPE_OPEN_ORDERS_POSTFIX', '_openorder_req'),
                'push_place_order' => env('KAFKA_BET_PLACED', 'PLACED-BET'),
            ];

            if ($swoole->wsTable->exist('data2Swt')) {
                $topicTable          = $swoole->topicTable;
                $minMaxRequestsTable = $swoole->minMaxRequestsTable;
                $ordersTable         = $swoole->ordersTable;
                $sportsTable         = $swoole->sportsTable;
                $providersTable      = $swoole->providersTable;
                $payloadsTable       = $swoole->payloadsTable;
                $initialTime         = Carbon::now()->format('H:i:s');

                while (!self::$quit) {
                    $newTime = Carbon::now()->format('H:i:s');

                    if ($nextTime->diffInSeconds($initialTime) >= 1) {
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
                                    self::pushToKafka($payload, $requestId, strtolower($minMaxRequest['provider']) . $kafkaTopics['req_minmax']);
                                }
                            }
                        }

                        foreach ($sportsTable AS $sKey => $sRow) {
                            $sportId = $sportsTable->get($sKey)['id'];

                            foreach ($providersTable AS $pKey => $pRow) {
                                $providerAlias = $providersTable->get($pKey)['alias'];
                                $requestId     = Str::uuid();
                                $requestTs     = self::milliseconds();

                                $payload = [
                                    'request_uid' => $requestId,
                                    'request_ts'  => $requestTs,
                                    'sub_command' => 'scrape',
                                    'command'     => 'bet'
                                ];

                                $payload['data'] = [
                                    'sport_id' => $sportId,
                                    'provider' => $providerAlias
                                ];

                                self::pushToKafka($payload, $requestId, $kafkaTopics['req_open_order']);
                            }
                        }

                        $initialTime = $newTime;
                    }

                    foreach ($payloadsTable AS $pKey => $pRow) {
                        if (strpos($pKey, 'place-bet-') === 0) {
                            $requestId = json_decode($payload)['request_uid'];
                            $payload = $pRow['payload'];

                            self::pushToKafka($payload, $requestId, $kafkaTopics['req_order']);
                        }

                        $payloadsTable->del($pKey);
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
