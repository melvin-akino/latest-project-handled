<?php

namespace App\Processes;

use App\Models\SystemConfiguration;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;
use Swoole\Coroutine;
use Swoole\Http\Server;
use Swoole\Process;
use App\Tasks\SocketDataPush;
use Exception;

class SocketConsume implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        $message = null;
        try {
            if ($swoole->data2SwtTable->exist('data2Swt')) {
                Log::info("Socket Consume Starts");

                $kafkaConsumer = app('KafkaConsumer');
                $kafkaConsumer->subscribe([env('KAFKA_SOCKET', 'SOCKET-DATA')]);
                $socketDataPush = app('SocketDataPush');

                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(0);
                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                        $payload = json_decode($message->payload, true);
                    
                        Task::deliver($socketDataPush->init($payload, $message->offset));

                        $kafkaConsumer->commitAsync($message);
                    }
                    Coroutine::sleep(0.01);
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
