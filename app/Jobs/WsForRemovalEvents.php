<?php

namespace App\Jobs;

use App\Models\{Events, MasterEvent, MasterEventLink, UserWatchlist};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Exception;

class WsForRemovalEvents implements ShouldQueue
{
    use Dispatchable;

    public function __construct($data, $providerId)
    {
        $this->data       = $data;
        $this->providerId = $providerId;
    }

    public function handle()
    {
        try {
            $server = app('swoole');

            $providerPriority = 0;
            $providerId       = 0;
            $providersTable   = $server->providersTable;

            foreach ($providersTable as $key => $provider) {
                if (empty($providerId) || $providerPriority > $provider['priority']) {
                    if ($provider['is_enabled']) {
                        $providerId       = $provider['id'];
                        $providerPriority = $provider['priority'];
                    }
                }
            }

            if (empty($providerId)) {
                Log::info("For Removal Event - No Providers Found");
                return;
            }

            $data = [];
            foreach ($this->data as $eventIdentifier) {
                $event = Events::where('event_identifier', $eventIdentifier)->first();
                $masterEvent = MasterEvent::find($event->master_event_id);
                if ($event && $this->providerId == $providerId) {
                    if ($masterEvent) {
                        UserWatchlist::where('master_event_id', $event->master_event_id)->delete();
                        MasterEvent::where('id', $event->master_event_id)->delete();
                        $data[] = $masterEvent->master_event_unique_id;
                    }
                }
                if ($event) {
                    $event->delete();
                }
            }

            foreach ($server->wsTable as $key => $row) {
                if (strpos($key, 'uid:') === 0 && $server->isEstablished($row['value'])) {
                    if (!empty($data)) {
                        $server->push($row['value'], json_encode(['getForRemovalEvents' => $data]));
                    }
                }
            }
            Log::info("For Removal Event - Processed");
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
