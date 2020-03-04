<?php

namespace App\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WsEvents implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $params)
    {
        $this->userId             = $userId;
        $this->master_league_name = $params[1];
        $this->schedule           = $params[2];
    }

    public function handle()
    {
        $server    = app('swoole');
        $fd        = $server->wsTable->get('uid:' . $this->userId);
        $getEvents = [];

        $providerPriority        = 0;
        $providerId              = 0;
        $providersTable          = $server->providersTable;
        $userProviderConfigTable = $server->userProviderConfigTable;

        /** TODO: Provider Maintenance Validation */

        foreach ($providersTable as $key => $provider) {
            if (empty($providerId) || $providerPriority > $provider['priority']) {
                if ($provider['is_enabled']) {
                    $providerId = $provider['id'];

                    $userProviderConfigSwtId = implode(':', [
                        "userId:" . $this->userId,
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

                    $providerPriority = $provider['priority'];
                }
            }
        }

        if (empty($providerId)) {
            throw new Exception('[VALIDATION_ERROR] No Providers found.');
        }

        $eventsTable = $server->eventsTable;

        foreach ($eventsTable as $key => $event) {var_dump($event);
            if ($event['master_league_name'] == $this->master_league_name && $event['game_schedule'] == $this->schedule) {
                $transformed = $server->transformedTable;

                if ($transformed->exist('uid:' . $event['master_event_unique_id'] . ":pId:" . $providerId)) {
                    $getEvents[] = json_decode($transformed->get('uid:' . $event['master_event_unique_id'] . ":pId:" . $providerId)['value'], true);

                    $server->wsTable->set('userEvents:' . $this->userId . ':uid:' . $event['master_event_unique_id'], ['value' => true]);
                }
            }
        }

        $server->push($fd['value'], json_encode([
            'getEvents' => $getEvents
        ]));
    }
}
