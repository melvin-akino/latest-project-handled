<?php

namespace App\Processes;

use App\Tasks\TransformKafkaMessageEvents;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;

class KafkaConsumeEvents implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            if ($swoole->wsTable->exist('data2Swt')) {
                $kafkaTable = $swoole->kafkaTable;

                $kafkaConsumer = resolve('KafkaConsumer');
                $kafkaConsumer->subscribe([env('KAFKA_SCRAPE_EVENTS')]);
                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(120 * 1000);
                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                        $kafkaTable->set('message:' . $message->offset, ['value' => $message->payload]);

                        Task::deliver(new TransformKafkaMessageEvents($message));

                        $kafkaConsumer->commitAsync($message);
                    } else {
                        Log::error(json_encode([$message]));
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
}
