<?php

namespace App\Jobs;

use App\Models\{EventMarket,
    Events,
    MasterEvent,
    MasterEventLink,
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
        if (!empty($this->data['MasterEventMarket']['isNew'])) {
            $masterEventMarketModel = MasterEventMarket::create($this->data['MasterEventMarket']['data']);
        } else {
            $masterEventMarketModel = MasterEventMarket::where('master_event_market_unique_id', $this->data['MasterEventMarket']['data']['master_event_market_unique_id'])
                ->first();
//            var_dump($this->data['MasterEventMarket']['data']);
//            var_dump('testing' . $masterEventMarketModel['id']);
        }

        if ($masterEventMarketModel) {
            $masterEventMarketId = $masterEventMarketModel->id;
            app('swoole')->eventMarketsTable[$this->data['MasterEventMarket']['swtKey']]['id'] = $masterEventMarketId;

            $eventMarketModel = EventMarket::create($this->data['EventMarket']['data']);
            $eventMarketId = $eventMarketModel->id;

            MasterEventMarketLink::create([
                'event_market_id' => $eventMarketId,
                'master_event_market_unique_id' => $masterEventMarketModel->master_event_market_unique_id
            ]);

            $this->data['MasterEventMarketLog']['data']['master_event_market_id'] = $masterEventMarketId;
            MasterEventMarketLog::create($this->data['MasterEventMarketLog']['data']);
        }
    }
}
