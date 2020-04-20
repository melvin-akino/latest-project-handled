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
                'req_settlements'  => env('KAFKA_SCRAPE_SETTLEMENT_POSTFIX', '_settlement_req'),
                'req_balance'      => env('KAFKA_SCRAPE_BALANCE_POSTFIX', '_balance_req')
            ];

            if ($swoole->wsTable->exist('data2Swt')) {
                $topicTable                 = $swoole->topicTable;
                $minMaxRequestsTable        = $swoole->minMaxRequestsTable;
                $ordersTable                = $swoole->ordersTable;
                $sportsTable                = $swoole->sportsTable;
                $providersTable             = $swoole->providersTable;
                $payloadsTable              = $swoole->payloadsTable;
                $providerAccountsTable      = $swoole->providerAccountsTable;
                $initialTime                = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                $providerAccountInitialTime = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                $betInitialTime             = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                $openOrderInitialTime       = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                $balanceTime                = 0;
                $systemConfigurationsTimers = [];

                while (!self::$quit) {
                    $newTime = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));

                    if ($newTime->diffInSeconds(Carbon::parse($initialTime)) >= 1) {
                        //Balance Process
                        $balanceTime++;

                        $refreshDBInterval = config('balance.refresh-db-interval');
                        if ($balanceTime % $refreshDBInterval == 0) {
                            $systemConfigurationsTimers = self::refreshBalanceDbConfig();
                        }

                        if (!empty($systemConfigurationsTimers)) {
                            foreach ($systemConfigurationsTimers as $systemConfigurationsTimer) {
                                if (!empty((int) $systemConfigurationsTimer['value'])) {
                                    if ($balanceTime % (int) $systemConfigurationsTimer['value'] == 0) {
                                        self::sendBalancePayload($systemConfigurationsTimer['type'], $kafkaTopics['req_balance'], $swoole);
                                    }
                                }
                            }
                        }
                        //END of Balance Process

                        //Minmax Process
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
                        //END of Minmax Process

                        foreach ($sportsTable AS $sKey => $sRow) {
                            $sportId = $sportsTable->get($sKey)['id'];

                            if ($newTime->diffInSeconds(Carbon::parse($openOrderInitialTime)) >= (60 * 10)) {
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
                                        'sport'     => $sportId,
                                        'provider'  => $providerAlias,
                                        'username'  => $username
                                    ];

                                    self::pushToKafka($payload, $requestId, $providerAlias . $kafkaTopics['req_open_order']);
                                }

                                $openOrderInitialTime = $newTime;
                            }

                            //checking if 30 minutest interval
                            // if ($newTime->diffInSeconds(Carbon::parse($providerAccountInitialTime)) >= (60 * 30)) {
                            if ($newTime->diffInSeconds(Carbon::parse($providerAccountInitialTime)) >= (60)) {
                                foreach ($providerAccountsTable AS $sKey => $sRow) {
                                    $providerAlias = strtolower($sRow['provider_alias']);
                                    $username      = $sRow['username'];

                                    $randomRangeInMinutes = rand(0, 10);

                                    $requestId     = Str::uuid();
                                    $requestTs     = self::milliseconds();

                                    $payload = [
                                        'request_uid' => $requestId,
                                        'request_ts'  => $requestTs,
                                        'sub_command' => 'scrape',
                                        'command'     => 'settlement'
                                    ];

                                    $payload['data'] = [
                                        'sport'     => $sportId,
                                        'provider'  => $providerAlias,
                                        'username'  => $username
                                    ];

                                    self::pushToKafka($payload, $requestId, $providerAlias . $kafkaTopics['req_settlements'], $randomRangeInMinutes);

                                    $providerAccountInitialTime = $newTime;
                                }
                            }
                        }

                        if ($newTime->diffInSeconds(Carbon::parse($betInitialTime)) >= 20) {
                            foreach ($payloadsTable AS $pKey => $pRow) {
                                if (strpos($pKey, 'place-bet-') === 0) {
                                    $payload   = json_decode($pRow['payload']);
                                    $requestId = $payload->request_uid;
                                    $provider  = $payload->data->provider;

                                    $dateNow = Carbon::now()->toDateTimeString();
                                    if (strtotime($dateNow) - strtotime($payload->data->created_at) < (int) $payload->data->orderExpiry) {
                                        self::pushToKafka((array) $payload, $requestId, $provider . $kafkaTopics['req_order']);
                                    } else {
                                        $payloadsTable->del($pKey);
                                    }
                                }
                            }
                            $betInitialTime = $newTime;
                        }

                        $initialTime = $newTime;
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
                'code' => $e->getCode()
            ]);
        } finally {
            if (env('KAFKA_LOG', false)) {
                Storage::append('producers-'. date('Y-m-d') . '.log', json_encode($message));
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

            $requestId = (string) Str::uuid();
            $requestTs = self::milliseconds();

            $payload = [
                'request_uid' => $requestId,
                'request_ts'  => $requestTs,
                'sub_command' => 'scrape',
                'command'     => 'balance'
            ];
            $payload['data'] = [
                'provider'  => $provider,
                'username'  => $username
            ];

            self::pushToKafka($payload, $requestId, $provider . $topic, rand(1, 180));
        }
    }
}
