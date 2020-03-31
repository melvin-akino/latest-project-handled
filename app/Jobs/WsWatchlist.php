<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $fd = $server->wsTable->get('uid:' . $this->userId);

        $watchlist = [];

        $providerPriority = 0;
        $providerId = 0;

        $providersTable = $server->providersTable;
        foreach ($providersTable as $key => $provider) {
            if (empty($providerPriority) || $providerPriority > $provider['priority']) {
                $providerPriority = $provider['priority'];
                $providerId = $provider['id'];
            }
        }

        $transformed = $server->transformedTable;
        // Id format for watchlistTable = 'userWatchlist:' . $userId . ':league:' . $league
        $wsTable = $server->wsTable;
        foreach ($wsTable as $key => $row) {
            if (strpos($key, 'userWatchlist:' . $this->userId . ':masterEventUniqueId:') === 0) {
                $uid = substr($key, strlen('userWatchlist:' . $this->userId . ':masterEventUniqueId:'));

                if ($transformed->exist('uid:' . $uid . ":pId:" . $providerId)) {
                    $watchlist[] = json_decode($transformed->get('uid:' . $uid . ":pId:" . $providerId)['value'],
                        true);;
                }
            }
        }

        $transformed = DB::table('master_leagues as ml')
            ->join('sports as s', 's.id', 'ml.sport_id')
            ->join('master_events as me', 'me.master_league_name', 'ml.master_league_name')
            ->join('master_event_markets as mem', 'mem.master_event_unique_id', 'me.master_event_unique_id')
            ->join('odd_types as ot', 'ot.id', 'mem.odd_type_id')
            ->join('master_event_market_links as meml', 'meml.master_event_market_unique_id',
                'mem.master_event_market_unique_id')
            ->join('event_markets as em', 'em.id', 'meml.event_market_id')
            ->join('user_watchlist as uw', 'uw.master_event_unique_id', 'me.master_event_unique_id')
            ->select('ml.sport_id', 'ml.master_league_name', 's.sport',
                'me.master_event_unique_id', 'me.master_home_team_name', 'me.master_away_team_name',
                'me.ref_schedule', 'me.game_schedule', 'me.score', 'me.running_time',
                'me.home_penalty', 'me.away_penalty', 'mem.odd_type_id', 'mem.master_event_market_unique_id',
                'mem.is_main', 'mem.market_flag',
                'ot.type', 'em.odds', 'em.odd_label', 'em.provider_id')
            ->where('uw.user_id', $this->userId)
            ->where('mem.is_main', true)
            ->whereNull('ml.deleted_at')
            ->distinct()->get();
        $data = [];
        array_map(function ($transformed) use (&$data) {
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
        }, $transformed->toArray());

        $watchlist = array_values($data);
        if (!empty($watchlist)) {
            $server->push($fd['value'], json_encode([
                'getWatchlist' => $watchlist
            ]));
        }
    }
}
