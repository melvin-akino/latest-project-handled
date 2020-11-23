<?php

namespace App\Handlers;

use App\Models\SystemConfiguration;
use Illuminate\Support\Facades\Log;
use Exception;

class MaintenanceTransformationHandler
{
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function init($data)
    {
        Log::info('MAINTENANCE -- Construct');

        $this->data = $data;
        return $this;
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

            $shouldProcess = false;
            if ($maintenance->exist('maintenance:' . strtolower($this->data->data->provider))) {
                if (
                    $maintenance->get('maintenance:' . strtolower($this->data->data->provider))['under_maintenance'] != (string) $this->data->data->is_undermaintenance
                ) {
                    $shouldProcess = true;
                }
            } else {
                $shouldProcess = true;
            }

            if ($shouldProcess) {
                $maintenance->set('maintenance:' . strtolower($this->data->data->provider), [
                    'under_maintenance' => (string) $this->data->data->is_undermaintenance,
                ]);

                SystemConfiguration::updateOrCreate([
                    'type' => strtoupper($this->data->data->provider) . '_MAINTENANCE'
                ], [
                    'value'  => $this->data->data->is_undermaintenance ? '1' : '0',
                    'module' => 'ProviderMaintenance'
                ]);

                foreach ($ws as $key => $row) {
                    if (strpos($key, 'uid:') !== false && $swoole->isEstablished($row['value'])) {
                        $swoole->push($ws->get($key)['value'], json_encode([
                            'getMaintenance' => [
                                'provider'          => strtolower($this->data->data->provider),
                                'under_maintenance' => $this->data->data->is_undermaintenance
                            ]
                        ]));
                    }
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
