<?php

namespace App\Jobs;

use App\Jobs\WsSettledBets;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class TransformKafkaMessageSettlement implements ShouldQueue
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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $swoole      = app('swoole');
        $orders      = $swoole->ordersTable;
        $providers   = $swoole->providersTable;
        $settlements = $this->data->data;

        foreach ($settlements AS $report) {
            $providerId       = $providers->get('providerAlias:' . $report->provider)['id'];
            $providerCurrency = $providers->get('providerAlias:' . $report->provider)['currency_id'];

            foreach ($orders AS $key => $row) {
                if ($row['bet_id'] == $report->bet_id) {
                    if ($row['status'] == 'SUCCESS') {
                        WsSettledBets::dispatch($report, $providerId, $providerCurrency);
                        $orders->del($key);
                    }
                }
            }
        }
    }
}
