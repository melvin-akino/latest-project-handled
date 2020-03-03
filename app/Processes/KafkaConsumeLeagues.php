<?php

namespace App\Processes;

use App\Jobs\Data2SWT;
use App\Jobs\TransformKafkaMessageLeagues;
use App\Models\Sport;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        // DB to SWT Initialization
        $swooleProcesses = [
            'Sports',
            'Providers',
            'MasterLeagues',
            'MasterTeams',
            'SportOddTypes',
            'MasterEvents',
            'MasterEventMarkets',
            'Transformed',
            'UserWatchlist'
        ];
        foreach ($swooleProcesses as $process) {
            $method = "db2Swt" . $process;
            self::{$method}($swoole);
        }

        $kafkaTable = $swoole->kafkaTable;

        $kafkaConsumer = resolve('KafkaConsumer');
        $kafkaConsumer->subscribe([env('KAFKA_SCRAPE_LEAGUES')]);
        while (!self::$quit) {
            $message = $kafkaConsumer->consume(120 * 1000);
            if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {

                 Log::debug(json_encode($message));
                 $kafkaTable->set('message:' . $message->offset, ['value' => $message->payload]);
                 Log::debug(json_encode($kafkaTable->get('message:' . $message->offset)));

                TransformKafkaMessageLeagues::dispatch($message);

                $kafkaConsumer->commitAsync($message);
            } else {
                Log::error(json_encode([$message]));
            }
        }
    }
}
