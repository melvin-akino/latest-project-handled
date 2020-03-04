<?php

namespace App\Processes;

use App\Jobs\TransformKafkaMessageEvents;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;

class KafkaConsumeEvents implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        if ($swoole->wsTable->exist('data2Swt')) {

            TransformKafkaMessageEvents::dispatch((object) ['payload' => self::testData()]);
            while(!self::$quit) {}

            $kafkaTable = $swoole->kafkaTable;

            $kafkaConsumer = resolve('KafkaConsumer');
            $kafkaConsumer->subscribe([env('KAFKA_SCRAPE_EVENTS')]);
            while (!self::$quit) {
                $message = $kafkaConsumer->consume(120 * 1000);
                if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                    $kafkaTable->set('message:' . $message->offset, ['value' => $message->payload]);
                    TransformKafkaMessageEvents::dispatch($message);

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

    private static function testData()
    {
        return json_encode([
            'request_uid' => '0eb7273d-07bc-4773-a4a2-1e193c9ac92e',
            'request_ts' => '1378761833768',
            'command' => 'odd',
            'sub_command' => 'transform',
            'data' => [
                    'provider' => 'hg',
                    'schedule' => 'early',
                    'sport' => 1,
                    'event_ids' => [
                        '4063057',
                        '4061099',
                        '4061215',
                        '4061247',
                        '4061251',
                        '4050099',
                        '4049793',
//                        '4058907',
//                        '4072095',
                        '4059043'
                    ],
                ],
        ]);
    }
}
