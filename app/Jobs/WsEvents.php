<?php

namespace App\Jobs;

use Exception;
use App\Models\{Game, MasterLeague, Order};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\{DB, Log};

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
        try {
            $server    = app('swoole');
            $fd        = $server->wsTable->get('uid:' . $this->userId);

            $providerPriority        = 0;
            $providerId              = 0;
            $providersTable          = $server->providersTable;
            $userProviderConfigTable = $server->userProviderConfigTable;
            $topicTable              = $server->topicTable;

            /** TODO: Provider Maintenance Validation */

            foreach ($providersTable as $key => $provider) {
                if (empty($providerId) || $providerPriority > $provider['priority']) {
                    if ($provider['is_enabled']) {
                        $providerId = $provider['id'];

                        $userProviderConfigSwtId = implode(':', [
                            "userId:" . $this->userId,
                            "pId:"    . $provider['id']
                        ]);

                        $doesExist = false;
                        foreach ($userProviderConfigTable as $k => $v) {
                            if ($k == $userProviderConfigSwtId) {
                                $doesExist = true;
                                break;
                            }
                        }
                        if ($doesExist) {
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

            $userBets     = Order::getOrdersByUserId($this->userId);
            $masterLeague = MasterLeague::where('name', $this->master_league_name)->first();
            $gameDetails  = Game::getGameDetails($masterLeague->id, $this->schedule);

            $data = [];
            $userId = $this->userId;
            array_map(function ($transformed) use (&$data, $topicTable, $userId, $userBets) {
                $mainOrOther = $transformed->is_main ? 'main' : 'other';
                if (empty($data[$transformed->master_event_unique_id])) {
                    $hasBet = false;

                    if (!empty($userBets)) {
                        $userOrderMarkets = array_column($userBets, 'market_id');
                        if (in_array($transformed->bet_identifier, $userOrderMarkets)) {
                            $hasBet = true;
                        }
                    }

                    $data[$transformed->master_event_unique_id] = [
                        'uid'           => $transformed->master_event_unique_id,
                        'sport_id'      => $transformed->sport_id,
                        'sport'         => $transformed->sport,
                        'provider_id'   => $transformed->provider_id,
                        'game_schedule' => $transformed->game_schedule,
                        'league_name'   => $transformed->master_league_name,
                        'running_time'  => $transformed->running_time,
                        'ref_schedule'  => $transformed->ref_schedule,
                        'has_bet'       => $hasBet
                    ];
                }

                if (empty($data[$transformed->master_event_unique_id]['home'])) {
                    $data[$transformed->master_event_unique_id]['home'] = [
                        'name' => $transformed->master_home_team_name,
                        'score' => empty($transformed->score) ? '' : array_values(explode(' - ', $transformed->score))[0],
                        'redcard' => $transformed->home_penalty
                    ];
                }

                if (empty($data[$transformed->master_event_unique_id]['away'])) {
                    $data[$transformed->master_event_unique_id]['away'] = [
                        'name' => $transformed->master_away_team_name,
                        'score' => empty($transformed->score) ? '' : array_values(explode(' - ', $transformed->score))[1],
                        'redcard' => $transformed->home_penalty
                    ];
                }

                if (empty($data[$transformed->master_event_unique_id]['market_odds'][$mainOrOther][$transformed->type][$transformed->market_flag])) {
                    $data[$transformed->master_event_unique_id]['market_odds'][$mainOrOther][$transformed->type][$transformed->market_flag] = [
                        'odds' => (double) $transformed->odds,
                        'market_id' => $transformed->master_event_market_unique_id
                    ];
                    if (!empty($transformed->odd_label)) {
                        $data[$transformed->master_event_unique_id]['market_odds'][$mainOrOther][$transformed->type][$transformed->market_flag]['points'] = $transformed->odd_label;
                    }

                    $doesExist = false;
                    foreach($topicTable as $topic) {
                        if ($topic['topic_name'] == 'market-id-' . $transformed->master_event_market_unique_id &&
                            $topic['user_id'] == $userId) {
                            $doesExist = true;
                            break;
                        }
                    }
                    if (empty($doesExist)) {
                        $topicTable->set('userId:' . $userId . ':unique:' . uniqid(), [
                            'user_id'       => $userId,
                            'topic_name'    => 'market-id-' . $transformed->master_event_market_unique_id
                        ]);
                    }
                }

            }, $gameDetails->toArray());
            $eventData = array_values($data);
            if (!empty($eventData)) {
                $server->push($fd['value'], json_encode([
                    'getEvents' => $eventData
                ]));
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
