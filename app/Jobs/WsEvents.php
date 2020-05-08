<?php

namespace App\Jobs;

use Exception;
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

            $transformed = DB::table('master_leagues as ml')
                ->join('sports as s', 's.id', 'ml.sport_id')
                ->join('master_events as me', 'me.master_league_name', 'ml.master_league_name')
                ->join('master_event_markets as mem', 'mem.master_event_unique_id', 'me.master_event_unique_id')
                ->join('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                ->join('master_event_market_links as meml', 'meml.master_event_market_unique_id', 'mem.master_event_market_unique_id')
                ->join('event_markets as em', 'em.id', 'meml.event_market_id')
                ->select('ml.sport_id', 'ml.master_league_name', 's.sport',
                    'me.master_event_unique_id', 'me.master_home_team_name', 'me.master_away_team_name',
                    'me.ref_schedule', 'me.game_schedule', 'me.score', 'me.running_time',
                    'me.home_penalty', 'me.away_penalty', 'mem.odd_type_id', 'mem.master_event_market_unique_id', 'mem.is_main', 'mem.market_flag',
                    'ot.type', 'em.odds', 'em.odd_label', 'em.provider_id')
                ->where('ml.master_league_name', $this->master_league_name)
                ->where('me.game_schedule', $this->schedule)
                ->where('mem.is_main', true)
                ->where('em.game_schedule', $this->schedule)
                ->whereNull('ml.deleted_at')
                ->whereNull('me.deleted_at')
                ->distinct()->get();
            $data = [];
            $userId = $this->userId;
            array_map(function ($transformed) use (&$data, $topicTable, $userId) {
                $mainOrOther = $transformed->is_main ? 'main' : 'other';
                if (empty($data[$transformed->master_event_unique_id])) {
                    $data[$transformed->master_event_unique_id] = [
                        'uid'           => $transformed->master_event_unique_id,
                        'sport_id'      => $transformed->sport_id,
                        'sport'         => $transformed->sport,
                        'provider_id'   => $transformed->provider_id,
                        'game_schedule' => $transformed->game_schedule,
                        'league_name'   => $transformed->master_league_name,
                        'running_time'  => $transformed->running_time,
                        'ref_schedule'  => $transformed->ref_schedule,
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

            }, $transformed->toArray());

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
