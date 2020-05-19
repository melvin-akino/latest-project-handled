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

class BetProduce implements CustomProcessInterface
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
                $payloadsTable              = $swoole->payloadsTable;
                $initialTime                = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));

                $startTime = $betInitialTime = $initialTime;
                while (!self::$quit) {
                    $newTime = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));

                    if ($newTime->diffInSeconds(Carbon::parse($initialTime)) >= 1) {

                        if ($newTime->diffInSeconds(Carbon::parse($betInitialTime)) >= 30) {
                            foreach ($payloadsTable AS $pKey => $pRow) {
                                if (strpos($pKey, 'place-bet-') === 0) {
                                    $payload   = json_decode($pRow['payload']);
                                    $requestId = $payload->request_uid;
                                    $provider  = $payload->data->provider;

                                    $dateNow = Carbon::now()->toDateTimeString();
                                    if (strtotime($dateNow) - strtotime($payload->data->created_at) < (int)$payload->data->orderExpiry) {
                                        self::pushToKafka((array)$payload, $requestId,
                                            $provider . env('KAFKA_SCRAPE_ORDER_REQUEST_POSTFIX', '_bet_req'));
                                    } else {
                                        $payloadsTable->del($pKey);
                                    }
                                }
                            }
                            $betInitialTime = $newTime;
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
}