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

class SettlementProduce implements CustomProcessInterface
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
                $sportsTable               = $swoole->sportsTable;
                $providerAccountsTable     = $swoole->providerAccountsTable;
                $initialTime               = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                $settlementTime            = 0;
                $systemConfigurationsTimer = null;
                $startTime                 = $initialTime;

                while (!self::$quit) {
                    $newTime = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                    if ($newTime->diffInSeconds(Carbon::parse($initialTime)) >= 1) {
                        $refreshDBInterval = config('settlement.refresh-db-interval');

                        if ($settlementTime % $refreshDBInterval == 0) {
                            $systemConfigurationsTimer = self::refreshSettlementDbConfig();
                        }

                        $settlementTime++;

                        if ($systemConfigurationsTimer) {
                            foreach ($sportsTable AS $sRow) {
                                $sportId = $sRow['id'];

                                if ($newTime->diffInSeconds(Carbon::parse($startTime)) >= $systemConfigurationsTimer->value) {
                                    foreach ($providerAccountsTable AS $pRow) {
                                        $providerAlias = strtolower($pRow['provider_alias']);
                                        $username      = $pRow['username'];
                                        $requestId     = (string) Str::uuid();
                                        $requestTs     = getMilliseconds();
                                        $payload       = [
                                            'request_uid' => $requestId,
                                            'request_ts'  => $requestTs,
                                            'sub_command' => 'scrape',
                                            'command'     => 'settlement'
                                        ];

                                        $payload['data'] = [
                                            'sport'    => $sportId,
                                            'provider' => $providerAlias,
                                            'username' => $username
                                        ];

                                        KafkaPush::dispatch(
                                            $providerAlias . env('KAFKA_SCRAPE_SETTLEMENT_POSTFIX', '_settlement_req'),
                                            $payload,
                                            $requestId
                                        );
                                        // add sleep to prevent detecting as bot
                                        sleep(random_int(60, 300));

                                        $startTime = $newTime;
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

    private static function refreshSettlementDbConfig()
    {
        return SystemConfiguration::where('type', 'SETTLEMENTS_REQUEST_TIMER')->first();
    }

    private static function milliseconds()
    {
        $mt = explode(' ', microtime());

        return bcadd($mt[1], $mt[0], 8);
    }
}
