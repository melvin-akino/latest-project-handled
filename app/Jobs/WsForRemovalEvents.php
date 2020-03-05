<?php

namespace App\Jobs;

use App\Models\{Events, MasterEvent, MasterEventLink, UserWatchlist};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
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
            throw new Exception('[VALIDATION_ERROR] No Providers found.');
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
                            'user_id'     => $this->userId,
                            'provider_id' => $provider['id'],
                            'active'      => $provider['is_enabled'],
                        ]
                    );
                }

                $server->push($row['value'], json_encode(['getForRemovalEvents' => $this->data]));
            }
        }

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
                }
            }
        }
    }
}
