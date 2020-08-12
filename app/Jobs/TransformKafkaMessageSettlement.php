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
                preg_match_all('!\d+!', $row['bet_id'], $mlBetIdArray);
                preg_match_all('!\d+!', $report->bet_id, $providerBetIdArray);

                $mlBetIdArrayIndex0 = $mlBetIdArray[0];
                $mlBetId = end($mlBetIdArrayIndex0);

                $providerBetIdArrayIndex0 = $providerBetIdArray[0];
                $providerBetId = end($providerBetIdArrayIndex0);

                if ($mlBetId == $providerBetId) {
                    if ($row['status'] == 'SUCCESS' || ($row['status'] == 'PENDING' && !empty($row['bet_id']))) {
                        WsSettledBets::dispatch($report, $providerId, $providerCurrency);

                        $orders->del($key);
                    }
                }
            }
        }
    }
}
