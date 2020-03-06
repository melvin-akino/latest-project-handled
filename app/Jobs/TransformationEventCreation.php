<?php

namespace App\Jobs;

use App\Models\{Events,
    MasterEvent,
    MasterEventLink};
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
        $swoole = app('swoole');
        if (!MasterEvent::where('master_event_unique_id', $this->data['MasterEvent']['data']['master_event_unique_id'])->exists()) {
            $masterEventModel = MasterEvent::create($this->data['MasterEvent']['data']);

            $eventModel = Events::create($this->data['Event']['data']);
            $rawEventId = $eventModel->id;

            $masterEventLink = MasterEventLink::create([
                'event_id' => $rawEventId,
                'master_event_unique_id' => $masterEventModel->master_event_unique_id
            ]);

            if ($masterEventModel && $eventModel && $masterEventLink) {
                $masterEventData = [
                    'master_event_unique_id' => $this->data['Event']['data']['master_event_unique_id'],
                    'master_league_name'     => $this->data['Event']['data']['master_league_name'],
                    'master_home_team_name'  => $this->data['Event']['data']['master_home_team_name'],
                    'master_away_team_name'  => $this->data['Event']['data']['master_away_team_name'],
                ];

                $swoole->eventsTable->set($this->data['MasterEvent']['swtKey'], $masterEventData);
            }
        }
    }
}
