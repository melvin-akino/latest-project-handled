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
            $kafkaProducer         = app('KafkaProducer');
            self::$producerHandler = new ProducerHandler($kafkaProducer);

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
                                $requestId = Str::uuid();
                                $requestTs = self::milliseconds();
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
                                    self::pushToKafka($payload, $requestId,
                                        strtolower($minMaxRequest['provider']) . env('KAFKA_SCRAPE_MINMAX_REQUEST_POSTFIX', '_minmax_req'));
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

    private static function refreshMinMaxDbConfig()
    {
        return SystemConfiguration::whereIn('type', [
            'MINMAX_INPLAY_REQUEST_TIMER',
            'MINMAX_TODAY_REQUEST_TIMER',
            'MINMAX_EARLY_REQUEST_TIMER'
        ])->get()->toArray();
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