<?php

namespace App\Processes;

use App\Models\SystemConfiguration;
use Illuminate\Support\Str;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;
use Carbon\Carbon;
use PrometheusMatric;

class MinMaxProduce implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;
    private static $producerHandler;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            self::$producerHandler = app('ProducerHandler');

            if ($swoole->data2SwtTable->exist('data2Swt')) {
                $minMaxRequestsTable        = $swoole->minMaxRequestsTable;
                $initialTime                = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                $minmaxTime                 = 0;
                $systemConfigurationsTimers = [];

                $startTime = $initialTime;
                while (!self::$quit) {
                    $newTime = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                    if ($newTime->diffInSeconds(Carbon::parse($initialTime)) >= 1) {
                        $refreshDBInterval = config('minmax.refresh-db-interval');
                        if ($minmaxTime % $refreshDBInterval == 0) {
                            $systemConfigurationsTimers = self::refreshMinMaxDbConfig();
                        }

                        $minmaxTime++;

                        if (!empty($systemConfigurationsTimers)) {
                            foreach ($minMaxRequestsTable as $minMaxRequest) {
                                $requestId = (string) Str::uuid();
                                $requestTs = getMilliseconds();
                                $scheduleFrequency = 1;
                                foreach ($systemConfigurationsTimers as $systemConfigurationsTimer) {
                                    if (
                                        ($systemConfigurationsTimer['type'] == 'MINMAX_INPLAY_REQUEST_TIMER' && $minMaxRequest['schedule'] == 'inplay') ||
                                        ($systemConfigurationsTimer['type'] == 'MINMAX_TODAY_REQUEST_TIMER' && $minMaxRequest['schedule'] == 'today') ||
                                        ($systemConfigurationsTimer['type'] == 'MINMAX_EARLY_REQUEST_TIMER' && $minMaxRequest['schedule'] == 'early')
                                    ) {
                                        $scheduleFrequency = $systemConfigurationsTimer['value'];
                                        break;
                                    }
                                }

                                $nowTime = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                                if ($nowTime->diffInSeconds(Carbon::parse($startTime)) % $scheduleFrequency == 0) {
                                    $payload         = [
                                        'request_uid' => $requestId,
                                        'request_ts'  => $requestTs,
                                        'sub_command' => 'scrape',
                                        'command'     => 'minmax'
                                    ];
                                    $payload['data'] = $minMaxRequest;

                                    PrometheusMatric::MakeMatrix('request_market_id_total',
                                        'Min-max  total number of  market id  pushed .', $minMaxRequest['market_id']);

                                    kafkaPush(
                                        strtolower($minMaxRequest['provider']) . env('KAFKA_SCRAPE_MINMAX_REQUEST_POSTFIX', '_minmax_req'),
                                        $payload,
                                        $requestId
                                    );

                                    $toLogs = [
                                        "class"       => "MinMaxProduce",
                                        "message"     => [
                                            "payload_sent" => $payload
                                        ],
                                        "module"      => "PROCESS",
                                        "status_code" => 200,
                                    ];
                                    monitorLog('monitor_process', 'info', $toLogs);
                                }
                            }
                        }
                        $initialTime = $newTime;
                    }
                    usleep(1000000);
                }
            }
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "MinMaxProduce",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "PRODUCE_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_process', 'error', $toLogs);
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }

    private static function refreshMinMaxDbConfig()
    {
        return SystemConfiguration::whereIn('type', [
            'MINMAX_INPLAY_REQUEST_TIMER',
            'MINMAX_TODAY_REQUEST_TIMER',
            'MINMAX_EARLY_REQUEST_TIMER'
        ])->get()->toArray();
    }
}
