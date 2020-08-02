<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Exception;

class TransformKafkaMessageMaintenance implements ShouldQueue
{
    use Dispatchable;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
        Log::info('MAINTENANCE -- Construct');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $swoole      = app('swoole');
        $ws          = $swoole->wsTable;
        $maintenance = $swoole->maintenanceTable;

        try {
            if (!isset($this->data->command)) {
                Log::error('MAINTENANCE -- Invalid Payload Command');
            }

            $payloadMaintenance = $this->data->data->under_maintenance == true ? "true" : "false";

            if ($maintenance['maintenance:' . strtolower($this->data->data->provider)]['under_maintenance'] == $payloadMaintenance) {
                Log::info('MAINTENANCE: Skip -- No Changes');

                return true;
            }

            $maintenance->set('maintenance:' . strtolower($this->data->data->provider), [
                'under_maintenance' => $this->data->data->under_maintenance == true ? "true" : "false",
            ]);

            foreach ($ws AS $key => $row) {
                if (strpos($key, 'uid:') !== false) {
                    $swoole->push($ws->get($key)['value'], json_encode([
                        'getMaintenance' => [
                            'provider'          => strtoupper($this->data->data->provider),
                            'under_maintenance' => $this->data->data->under_maintenance == true ? "true" : "false",
                        ]
                    ]));
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function finish()
    {
        Log::info("MAINTENANCE -- Done");
    }
}
