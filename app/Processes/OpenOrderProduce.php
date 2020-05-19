<?php

namespace App\Processes;

use App\Handlers\ProducerHandler;
use App\Jobs\KafkaPush;
use App\Models\SystemConfiguration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;
use Carbon\Carbon;
use PrometheusMatric;

class OpenOrderProduce implements CustomProcessInterface
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

            if ($swoole->data2SwtTable->exist('data2Swt')) {
                $sportsTable                = $swoole->sportsTable;
                $providerAccountsTable      = $swoole->providerAccountsTable;
                $initialTime                = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                $openOrderTime             = 0;
                $systemConfigurationsTimer  = null;

                $startTime = $initialTime;
                while (!self::$quit) {
                    $newTime = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                    if ($newTime->diffInSeconds(Carbon::parse($initialTime)) >= 1) {
                        $refreshDBInterval = config('open_order.refresh-db-interval');
                        if ($openOrderTime % $refreshDBInterval == 0) {
                            $systemConfigurationsTimer = self::refreshOpenOrderDbConfig();
                        }

                        $openOrderTime++;

                        if ($systemConfigurationsTimer) {
                            foreach ($sportsTable AS $sKey => $sRow) {
                                $sportId = $sportsTable->get($sKey)['id'];

                                if ($newTime->diffInSeconds(Carbon::parse($startTime)) >= $systemConfigurationsTimer->value) {
                                    foreach ($providerAccountsTable AS $pKey => $pRow) {
                                        $providerAlias = strtolower($pRow['provider_alias']);
                                        $username      = $pRow['username'];
                                        $requestId     = Str::uuid();
                                        $requestTs     = self::milliseconds();

                                        $payload = [
                                            'request_uid' => $requestId,
                                            'request_ts'  => $requestTs,
                                            'sub_command' => 'scrape',
                                            'command'     => 'orders'
                                        ];

                                        $payload['data'] = [
                                            'sport'    => $sportId,
                                            'provider' => $providerAlias,
                                            'username' => $username
                                        ];

                                        self::pushToKafka($payload, $requestId,
                                            $providerAlias . env('KAFKA_SCRAPE_OPEN_ORDERS_POSTFIX', '_openorder_req'));
                                    }

                                    $startTime = $newTime;
                                }
                            }
                        }
                        $initialTime = $newTime;
                    }
                    usleep(1000000);
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

    private static function refreshOpenOrderDbConfig()
    {
        return SystemConfiguration::where('type', 'OPEN_ORDERS_REQUEST_TIMER')->first();
    }

    private static function milliseconds()
    {
        $mt = explode(' ', microtime());
        return bcadd($mt[1], $mt[0], 8);
    }

    private static function pushToKafka(array $message = [], string $key, string $kafkaTopic, int $delayInSeconds = 0)
    {
        try {
            if (empty($delayInMinutes)) {
                self::$producerHandler->setTopic($kafkaTopic)
                    ->send($message, $key);
            } else {
                KafkaPush::dispatch($kafkaTopic, $message, $key)->delay(now()->addSeconds($delayInSeconds));
            }
        } catch (Exception $e) {
            Log::critical('Sending Kafka Message Failed', [
                'error' => $e->getMessage(),
                'code'  => $e->getCode()
            ]);
        } finally {
            Log::channel('kafkaproducelog')->info(json_encode($message));
        }
    }
}