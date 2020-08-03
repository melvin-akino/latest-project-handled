<?php

namespace App\Processes;

use App\Jobs\TransformKafkaMessageMaintenance;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;

class MaintenanceConsume implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            $kafkaConsumer = resolve('KafkaConsumer');
            $kafkaConsumer->subscribe([
                env('KAFKA_SCRAPE_MAINTENANCE', 'PROVIDER-MAINTENANCE')
            ]);

            Log::info("Maintenance Consume Starts");

            while (!self::$quit) {
                $message = $kafkaConsumer->consume(0);

                if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                    $payload            = json_decode($message->payload);
                    $payloadMaintenance = $payload->data->under_maintenance == true ? "true" : "false";

                    if (empty($payload->data)) {
                        Log::info("Maintenance Transformation ignored - No Data Found");
                    } else {
                        if ($swoole->maintenanceTable['maintenance:' . strtolower($payload->data->provider)]['under_maintenance'] == $payloadMaintenance) {
                            Log::info('MAINTENANCE: Skip -- No Changes');
                        } else {
                            Log::info('Maintenance calling Task Worker');
                            TransformKafkaMessageMaintenance::dispatchNow($payload);
                        }
                    }

                    $kafkaConsumer->commit($message);
                }

                usleep(100000);
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
