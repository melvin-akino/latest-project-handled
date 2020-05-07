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

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        try {
            $server    = app('swoole');

            $providerPriority        = 0;
            $providerId              = 0;
            $providersTable          = $server->providersTable;
            $userProviderConfigTable = $server->userProviderConfigTable;

            foreach ($providersTable as $key => $provider) {
                if (empty($providerId) || $providerPriority > $provider['priority']) {
                    if ($provider['is_enabled']) {
                        $providerId = $provider['id'];
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
                $event->delete();

                if($event->provider_id == $providerId) {
                    $eventLink = MasterEventLink::where('event_id', $event->id)->first();
                    if ($eventLink) {
                        $userWatchlist = UserWatchlist::where('master_event_unique_id', $eventLink->master_event_unique_id);
                        $userWatchlist->delete();
                        $masterEvent = MasterEvent::where('master_event_unique_id', $eventLink->master_event_unique_id);
                        $masterEvent->delete();
                        $data[] = $eventLink->master_event_unique_id;
                    }
                }
            }

            foreach ($server->wsTable as $key => $row) {
                if (strpos($key, 'uid:') === 0 && $server->isEstablished($row['value'])) {
                    $userId = substr($key, strlen('uid:'));

                    $userProviderConfigSwtId = implode(':', [
                        "userId:" . $userId,
                        "pId:"    . $provider['id']
                    ]);

                    if ($userProviderConfigTable->exists($userProviderConfigSwtId)) {
                        if ($userProviderConfigTable->get($userProviderConfigSwtId)['active']) {
                            $providerId = $userProviderConfigTable->get($userProviderConfigSwtId)['provider_id'];
                        }
                    } else {
                        $userProviderConfigTable->set($userProviderConfigSwtId,
                            [
                                'user_id'     => $userId,
                                'provider_id' => $provider['id'],
                                'active'      => $provider['is_enabled'],
                            ]
                        );
                    }

                    if (!empty($data)) {
                        $server->push($row['value'], json_encode(['getForRemovalEvents' => $data]));
                    }
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
