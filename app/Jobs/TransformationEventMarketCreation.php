<?php

namespace App\Jobs;

use App\Models\{EventMarket,
    MasterEventMarket,
    MasterEventMarketLink,
    MasterEventMarketLog};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class TransformationEventMarketCreation implements ShouldQueue
{
    use Dispatchable;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        $swoole = app('swoole');

        if (!empty($this->data['MasterEventMarket']['isNew'])) {
            $masterEventMarketModel = MasterEventMarket::create($this->data['MasterEventMarket']['data']);
        } else {
            $masterEventMarketModel = MasterEventMarket::where('master_event_market_unique_id', $this->data['MasterEventMarket']['data']['master_event_market_unique_id'])
                ->first();
        }

        if ($masterEventMarketModel) {
            $masterEventMarketId = $masterEventMarketModel->id;

            $eventMarketModel = EventMarket::create($this->data['EventMarket']['data']);
            $eventMarketId = $eventMarketModel->id;

            MasterEventMarketLink::create([
                'event_market_id' => $eventMarketId,
                'master_event_market_unique_id' => $masterEventMarketModel->master_event_market_unique_id
            ]);

            $this->data['MasterEventMarketLog']['data']['master_event_market_id'] = $masterEventMarketId;
            MasterEventMarketLog::create($this->data['MasterEventMarketLog']['data']);

            $array = [
                'odd_type_id'                   => $this->data['MasterEventMarket']['data']['odd_type_id'],
                'master_event_market_unique_id' => $this->data['MasterEventMarket']['data']['master_event_market_unique_id'],
                'master_event_unique_id'        => $this->data['MasterEventMarket']['data']['master_event_unique_id'],
                'provider_id'                   => $this->data['EventMarket']['data']['provider_id'],
                'odds'                          => $this->data['EventMarket']['data']['odds'],
                'odd_label'                     => $this->data['EventMarket']['data']['odd_label'],
                'bet_identifier'                => $this->data['EventMarket']['data']['bet_identifier'],
                'is_main'                       => $this->data['MasterEventMarket']['data']['is_main'],
                'market_flag'                   => $this->data['MasterEventMarket']['data']['market_flag'],
            ];

            $swoole->eventMarketsTable->set($this->data['MasterEventMarket']['swtKey'], $array);
        }
    }
}
