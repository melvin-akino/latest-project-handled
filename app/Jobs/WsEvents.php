<?php

namespace App\Jobs;

use Exception;
use App\Models\{
    Game,
    MasterLeague,
    Order,
    Timezones,
    UserConfiguration,
    Provider,
    UserProviderConfiguration
};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
            $server = app('swoole');
            $fd     = $server->wsTable->get('uid:' . $this->userId);

            $topicTable = $server->topicTable;

            $providerId = Provider::getMostPriorityProvider($this->userId);

            $userBets     = Order::getOrdersByUserId($this->userId);
            $masterLeague = MasterLeague::where('name', $this->master_league_name)->first();
            $gameDetails  = Game::getGameDetails($masterLeague->id, $this->schedule, $providerId);

            $data          = [];
            $userId        = $this->userId;
            $userTz        = "Etc/UTC";
            $getUserConfig = UserConfiguration::getUserConfig($userId)
                                              ->where('type', 'timezone')
                                              ->first();

            if ($getUserConfig) {
                $userTz = Timezones::find($getUserConfig->value)->name;
            }
            array_map(function ($transformed) use (&$data, $topicTable, $userId, $userBets, $userTz) {
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
                        'ref_schedule'  => Carbon::createFromFormat("Y-m-d H:i:s", $transformed->ref_schedule, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s"),
                        'has_bet'       => $hasBet
                    ];
                }

                if (empty($data[$transformed->master_event_unique_id]['home'])) {
                    $data[$transformed->master_event_unique_id]['home'] = [
                        'name'    => $transformed->master_home_team_name,
                        'score'   => empty($transformed->score) ? '' : array_values(explode(' - ', $transformed->score))[0],
                        'redcard' => $transformed->home_penalty
                    ];
                }

                if (empty($data[$transformed->master_event_unique_id]['away'])) {
                    $data[$transformed->master_event_unique_id]['away'] = [
                        'name'    => $transformed->master_away_team_name,
                        'score'   => empty($transformed->score) ? '' : array_values(explode(' - ', $transformed->score))[1],
                        'redcard' => $transformed->home_penalty
                    ];
                }

                if (empty($data[$transformed->master_event_unique_id]['market_odds'][$mainOrOther][$transformed->type][$transformed->market_flag])) {
                    $data[$transformed->master_event_unique_id]['market_odds'][$mainOrOther][$transformed->type][$transformed->market_flag] = [
                        'odds'      => (double) $transformed->odds,
                        'market_id' => $transformed->master_event_market_unique_id
                    ];
                    if (!empty($transformed->odd_label)) {
                        $data[$transformed->master_event_unique_id]['market_odds'][$mainOrOther][$transformed->type][$transformed->market_flag]['points'] = $transformed->odd_label;
                    }

                    $doesExist = false;
                    foreach ($topicTable as $topic) {
                        if ($topic['topic_name'] == 'market-id-' . $transformed->master_event_market_unique_id &&
                            $topic['user_id'] == $userId) {
                            $doesExist = true;
                            break;
                        }
                    }
                    if (empty($doesExist)) {
                        $topicTable->set('userId:' . $userId . ':unique:' . uniqid(), [
                            'user_id'    => $userId,
                            'topic_name' => 'market-id-' . $transformed->master_event_market_unique_id
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
