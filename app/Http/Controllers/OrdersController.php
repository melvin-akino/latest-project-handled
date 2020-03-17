<?php

namespace App\Http\Controllers;

use App\Models\{
    EventMarket,
    MasterEvent,
    MasterEventMarket,
    MasterEventMarketLog,
    OddType,
    Provider,
    Sport
};
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    /**
     * Get Event Details from the given parameter to display information
     * in the Bet Slip Interface
     *
     * @param  string $memUID
     * @return json
     */
    public function getEventMarketsDetails(string $memUID)
    {
        try {
            $masterEventMarket = MasterEventMarket::where('master_event_market_unique_id', $memUID);

            if (!$masterEventMarket->exists()) {
                return response()->json([
                    'status'      => false,
                    'status_code' => 404,
                    'message'     => trans('generic.not-found')
                ], 404);
            }

            $masterEventMarket = $masterEventMarket->first([
                'is_main',
                'market_flag',
                'odd_type_id',
                'master_event_unique_id'
            ]);

            $masterEvent = MasterEvent::where('master_event_unique_id', $masterEventMarket->master_event_unique_id);

            if (!$masterEvent->exists()) {
                return response()->json([
                    'status'      => false,
                    'status_code' => 404,
                    'message'     => trans('generic.not-found')
                ], 404);
            }

            $masterEvent = $masterEvent->first();

            $data = [
                'league_name'   => $masterEvent->master_league_name,
                'home'          => $masterEvent->master_home_team_name,
                'away'          => $masterEvent->master_away_team_name,
                'game_schedule' => $masterEvent->game_schedule,
                'ref_schedule'  => $masterEvent->ref_schedule,
                'running_time'  => $masterEvent->running_time,
                'score'         => $masterEvent->score,
                'home_penalty'  => $masterEvent->home_penalty,
                'away_penalty'  => $masterEvent->away_penalty,
                'market_flag'   => $masterEventMarket->market_flag,
                'odd_type'      => OddType::getTypeByID($masterEventMarket->odd_type_id),
                'sport'         => Sport::getNameByID($masterEvent->sport_id),
            ];

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $data
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /**
     * Get Event Market Logs to keep track on every updates
     * the market offers
     *
     * @param  string $memUID
     * @return json
     */
    public function getEventMarketLogs(string $memUID)
    {
        try {
            $data = [];

            $providers = Provider::getActiveProviders()->get([
                'id',
                'alias'
            ]);

            $masterEventMarket = MasterEventMarket::where('master_event_market_unique_id', $memUID);

            if (!$masterEventMarket->exists()) {
                return response()->json([
                    'status'      => false,
                    'status_code' => 404,
                    'message'     => trans('generic.not-found')
                ], 404);
            }

            $masterEventMarket = $masterEventMarket->first();

            $eventLogs = MasterEventMarketLog::where('master_event_market_id', $masterEventMarket->id)
                ->orderBy('created_at', 'asc')
                ->get()
                ->toArray();

            foreach ($providers AS $provider) {
                $data[$provider->alias] = array_filter($eventLogs, function ($row) use ($provider) {
                    return $row['provider_id'] == $provider->id;
                });
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $data
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    public function postPlaceBet(Request $request)
    {
        try {
            $swt    = app('swoole');
            $topics = $swt->topicTable;
            $orders = $swt->ordersTable;

            if ($request->betType == "BEST_PRICE") {
                $data[] = [
                    'betType'     => $request->betType,
                    'stake'       => $request->stake,
                    'min'         => $request->min,
                    'max'         => $request->max,
                    'price'       => $request->price,
                    'provider_id' => $request->provider_id,
                    'orderExpiry' => $request->orderExpiry,
                    'market_id'   => $request->market_id,
                ];
            } else {
                $data = $request->all();
            }

            $betType   = "";
            $return    = "";
            $prevStake = 0;

            foreach ($data AS $row) {
                $betType        = $row['betType'];
                $hasComputation = false;
                $userProvider   = UserProviderConfiguration::where('provider_id', $row['provider_id']);
                $userProvider   = Provider::find($userProvider->count() == 0 ? $row['provider_id'] : $userProvider->provider_id);

                /** TO DO: Wallet Balance Sufficiency Check */

                if (!$userProvider->is_enabled) {
                    return response()->json([
                        'status'      => false,
                        'status_code' => 400,
                        'message'     => trans('generic.bad-request')
                    ], 400);
                }

                if ($userProvider->alias == "HG") {
                    $hasComputation = true;
                }

                if (!in_array($row['provider_id'], $userProvider->toArray())) {
                    return response()->json([
                        'status'      => false,
                        'status_code' => 404,
                        'message'     => trans('generic.not-found')
                    ], 404);
                }

                $query = DB::table('master_events AS me')
                    ->join('master_event_markets AS mem', 'me.master_event_unique_id', '=', 'mem.master_event_unique_id')
                    ->join('event_markets AS em', function ($join) {
                        $join->on('me.master_event_unique_id', '=', 'em.master_event_unique_id');
                        $join->on('mem.odd_type_id', '=', 'em.odd_type_id');
                        $join->on('mem.is_main', '=', 'em.is_main');
                        $join->on('mem.market_flag', '=', 'em.market_flag');
                    })
                    ->whereNull('me.deleted_at')
                    ->where('mem.master_event_market_unique_id', $row['market_id'])
                    ->orderBy('mem.odd_type_id', 'asc')
                    ->select([
                        'me.sport_id',
                        'me.master_event_unique_id',
                        'me.master_league_name',
                        'me.master_home_team_name',
                        'me.master_away_team_name',
                        'me.game_schedule',
                        'me.running_time',
                        'me.score',
                        'mem.master_event_market_unique_id',
                        'mem.is_main',
                        'mem.market_flag',
                        'mem.odd_type_id',
                        'em.bet_identifier',
                        'em.provider_id',
                    ])
                    ->first();

                if (!$query->master_event_unique_id) {
                    return response()->json([
                        'status'      => false,
                        'status_code' => 404,
                        'message'     => trans('generic.not-found')
                    ], 404);
                }

                if ($hasComputation) {
                    $actualStake = $row['stake'] / ($userProvider->punter_percentage / 100);
                } else {
                    $actualStake = $row['stake'];
                }

                $payloadStake = $prevStake == 0 ? $row['stake'] : $prevStake;

                if ($row['betType'] == "BEST_PRICE") {
                    $prevStake = $row['stake'] - $row['max'];
                }

                $payload = [
                    'odds'          => $row['price'],
                    'stake'         => $payloadStake,
                    'to_win'        => $payloadStake * $row['price'],
                    'actual_stake'  => $actualStake,
                    'actual_to_win' => $actualStake * $row['price'],
                    'market_id'     => $query->bet_identifier,
                    'event_id'      => explode('-', $query->master_event_unique_id)[3],
                    'score'         => $query->score,
                    'orderExpiry'   => $row['orderExpiry'],
                ];

                if ($row['betType'] == "FAST_BET") {
                    $prevStake = $prevStake == 0 ? $row['stake'] - $row['max'] : $prevStake - $row['max'];
                }

                $topicsId = implode(':', [
                    "userId:" . auth()->user()->id,
                    "unique:" . uniqid(),
                ]);

                if (!$topics->exists($topicsId)) {
                    $topics->set($topicsId, [
                        'user_id'    => auth()->user()->id,
                        'topic_name' => "order-" . uniqid()
                    ]);
                }

                $ordersId = "order-" . uniqid();

                if (!$orders->exists($ordersId)) {
                    $orders->set($ordersId, $payload);
                }
            }

            if ($betType == "BEST_PRICE") {
                $return = $prevStake > 0 ? trans('game.bet.best-price.continue') : trans('game.bet.best-price.success');
            }

            if ($betType == "FAST_BET") {
                $return = $prevStake > 0 ? trans('game.bet.fast-bet.continue') : trans('game.bet.fast-bet.success');
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $return,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    private static function milliseconds()
    {
        $mt = explode(' ', microtime());

        return bcadd($mt[1], $mt[0], 8);
    }
}
