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

class TransformationEventCreation implements ShouldQueue
{
    use Dispatchable;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        if (!MasterEvent::where('master_event_unique_id', $this->data['MasterEvent']['data']['master_event_unique_id'])->exists()) {
            $masterEventModel = MasterEvent::create($this->data['MasterEvent']['data']);
            $masterEventId = $masterEventModel->id;
            app('swoole')->eventsTable[$this->data['MasterEvent']['swtKey']]['id'] = $masterEventId;

            $eventModel = Events::create($this->data['Event']['data']);
            $rawEventId = $eventModel->id;

            MasterEventLink::create([
                'event_id' => $rawEventId,
                'master_event_unique_id' => $masterEventModel->master_event_unique_id
            ]);
        }
    }
}
