<?php

namespace App\Tasks;

use App\Jobs\WsSettledBets;
use Hhxsv5\LaravelS\Swoole\Task\Task;

class TransformKafkaMessageSettlement extends Task
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

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
