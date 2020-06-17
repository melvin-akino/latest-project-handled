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
                $initialTime                = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                $balanceTime                = 0;
                $systemConfigurationsTimers = [];

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
                                if (!empty((int)$systemConfigurationsTimer['value']) && $balanceTime % (int) $systemConfigurationsTimer['value'] == 0) {
                                    self::sendBalancePayload($systemConfigurationsTimer['type'],
                                        env('KAFKA_SCRAPE_BALANCE_POSTFIX', '_balance_req'), $swoole);
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
            $requestTs = getMilliseconds();

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

            KafkaPush::dispatch($provider . $topic, $payload, $requestId);
        }
    }
}
