<?php

namespace App\Processes;

use App\Jobs\TransformKafkaMessageLeagues;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;

class KafkaConsumeLeagues implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        if ($swoole->wsTable->exist('data2Swt')) {
            $kafkaTable = $swoole->kafkaTable;

            $kafkaConsumer = resolve('KafkaConsumer');
            $kafkaConsumer->subscribe([env('KAFKA_SCRAPE_LEAGUES')]);
            while (!self::$quit) {
                $message = $kafkaConsumer->consume(120 * 1000);
                if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                    $kafkaTable->set('message:' . $message->offset, ['value' => $message->payload]);
                    TransformKafkaMessageLeagues::dispatch($message);

                    if (env('APP_ENV') != 'production') {
                        Log::debug(json_encode($message));
                        Log::debug(json_encode($kafkaTable->get('message:' . $message->offset)));
                    }

                    $kafkaConsumer->commitAsync($message);
                } else {
                    Log::error(json_encode([$message]));
                }
            }
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
