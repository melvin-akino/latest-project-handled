<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\{Game, Order};

class WsWatchlist implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function handle()
    {
        $server = app('swoole');
        $fd     = $server->wsTable->get('uid:' . $this->userId);

        $watchlist = [];

        $providerPriority = 0;
        $providerId       = 0;

        $providersTable = $server->providersTable;
        foreach ($providersTable as $key => $provider) {
            if (empty($providerPriority) || $providerPriority > $provider['priority']) {
                $providerPriority = $provider['priority'];
                $providerId       = $provider['id'];
            }
        }

        $transformed = $server->transformedTable;
        // Id format for watchlistTable = 'userWatchlist:' . $userId . ':league:' . $league
        $userWatchlistTable = $server->userWatchlistTable;
        foreach ($userWatchlistTable as $key => $row) {
            $uid = substr($key, strlen('userWatchlist:' . $this->userId . ':masterEventUniqueId:'));

            if ($transformed->exist('uid:' . $uid . ":pId:" . $providerId)) {
                $watchlist[] = json_decode($transformed->get('uid:' . $uid . ":pId:" . $providerId)['value'],
                    true);;
            }
        }

        $userBets = Order::getOrdersByUserId($this->userId);

        $gameDetails = Game::getWatchlistGameDetails($this->userId);

        $data        = [];
        array_map(function ($transformed) use (&$data) {
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
                    'odds'      => (double)$transformed->odds,
                    'market_id' => $transformed->master_event_market_unique_id
                ];
                if (!empty($transformed->odd_label)) {
                    $data[$transformed->master_event_unique_id]['market_odds'][$mainOrOther][$transformed->type][$transformed->market_flag]['points'] = $transformed->odd_label;
                }
            }
        }, $gameDetails->toArray());

        $watchlist = array_values($data);
        if (!empty($watchlist)) {
            $server->push($fd['value'], json_encode([
                'getWatchlist' => $watchlist
            ]));
        }
    }
}
