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
use Storage;
use PrometheusMatric;

class BalanceProduce implements CustomProcessInterface
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
                $providerAccountsTable      = $swoole->providerAccountsTable;
                $initialTime                = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                $balanceTime                = 0;
                $systemConfigurationsTimers = [];

                $startTime = $initialTime;
                while (!self::$quit) {
                    $newTime = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                    if ($newTime->diffInSeconds(Carbon::parse($initialTime)) >= 1) {
                        $refreshDBInterval = config('balance.refresh-db-interval');
                        if ($balanceTime % $refreshDBInterval == 0) {
                            $systemConfigurationsTimers = self::refreshBalanceDbConfig();
                        }

                        $balanceTime++;

                        if (!empty($systemConfigurationsTimers)) {
                            foreach ($systemConfigurationsTimers as $systemConfigurationsTimer) {
                                if (!empty((int)$systemConfigurationsTimer['value'])) {
                                    if ($balanceTime % (int)$systemConfigurationsTimer['value'] == 0) {
                                        self::sendBalancePayload($systemConfigurationsTimer['type'],
                                            env('KAFKA_SCRAPE_BALANCE_POSTFIX', '_balance_req'), $swoole);
                                    }
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
            if (env('KAFKA_LOG', false)) {
                Storage::append('producers-' . date('Y-m-d') . '.log', json_encode($message));
            }
            Log::channel('kafkaproducelog')->info(json_encode($message));
        }
    }

    private static function refreshBalanceDbConfig()
    {
        return SystemConfiguration::whereIn('type', ['BET_VIP', 'BET_NORMAL'])->get()->toArray();
    }

    private static function sendBalancePayload($type, $topic, $swoole)
    {
        $providerAccounts = $swoole->providerAccountsTable;

        foreach ($providerAccounts as $providerAccount) {
            $username = $providerAccount['username'];
            $provider = strtolower($providerAccount['provider_alias']);

            $requestId = (string)Str::uuid();
            $requestTs = self::milliseconds();

            $payload         = [
                'request_uid' => $requestId,
                'request_ts'  => $requestTs,
                'sub_command' => 'scrape',
                'command'     => 'balance'
            ];
            $payload['data'] = [
                'provider' => $provider,
                'username' => $username
            ];

            self::pushToKafka($payload, $requestId, $provider . $topic, rand(1, 180));
        }
    }
}
