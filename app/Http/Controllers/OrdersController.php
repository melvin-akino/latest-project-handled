<?php

namespace App\Http\Controllers;

use App\User;
use App\Models\{
    Currency,
    ExchangeRate,
    EventMarket,
    MasterEvent,
    MasterEventMarket,
    MasterEventMarketLog,
    OddType,
    Provider,
    Sport,
    UserProviderConfiguration,
    Order,
    OrderLogs,
    UserWallet
};
use App\Models\CRM\{
    ProviderAccount,
    WalletLedger
};

use Illuminate\Http\Request;
use Illuminate\Support\{
    Facades\DB,
    Str
};

class OrdersController extends Controller
{
    /**
     * Get all orders made by the user using the parameters below
     *
     * @param  Request $request
     * @return json
     */
    public function myOrders(Request $request)
    {
        try {
            $conditions = [];

            !empty($request->status) ? !($request->status  == "All") ? $conditions[] = ['status', $request->status] : null : null;

            !empty($request->created_from) ? $conditions[] = ['created_at', '>=', $request->created_from] : null;
            !empty($request->created_to) ? $conditions[]   = ['created_at', '<=', $request->created_to] : !empty($request->created_from) ? ['created_at', '<=', now()] : null;

            !empty($request->settled_from) ? $conditions[] = ['settled_date', '>=', $request->settled_from] : null;
            !empty($request->settled_to) ? $conditions[]   = ['settled_date', '<=', $request->settled_to] : !empty($request->settled_to) ? ['settled_date', '<=', now()] : null;

            //Pagination part
            $page        = $request->has('page') ? $request->get('page') : 1;
            $limit       = $request->has('limit') ? $request->get('limit') : 25;
            $myAllOrders = Order::countAllOrders();

            if (!empty($myAllOrders)) {
                $myOrders = Order::getAllOrders($conditions, $page, $limit);

                foreach($myOrders as $myOrder) {
                    $data['orders'][] = [
                        'bet_id'        => $myOrder->bet_id,
                        'bet_selection' => $myOrder->bet_selection,
                        'provider'      => strtoupper($myOrder->alias),
                        'odds'          => $myOrder->odds,
                        'stake'         => $myOrder->stake,
                        'towin'         => $myOrder->to_win,
                        'created'       => $myOrder->created_at,
                        'settled'       => $myOrder->settled_date,
                        'pl'            => $myOrder->profit_loss,
                    ];
                }

                $data['total_count'] = $myAllOrders;
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => !empty($data) ? $data : null
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
     * Get Event Details from the given parameter to display information
     * in the Bet Slip Interface
     *
     * @param  string $memUID
     * @return json
     */
    public function getEventMarketsDetails(string $memUID)
    {
        try {
            $userProvider = UserProviderConfiguration::where('user_id', auth()->user()->id);

            if ($userProvider->exists()) {
                $userProvider = $userProvider->where('active', true);
            } else {
                $userProvider = Provider::where('is_enabled', true);
            }

            $userProvider = $userProvider->orderBy('priority', 'ASC')
                ->first();

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

            $getOtherMarkets = DB::table('event_markets AS em')
                ->join('master_event_markets AS mem', function ($join) {
                    $join->on('em.master_event_unique_id', '=', 'mem.master_event_unique_id');
                    $join->on('em.odd_type_id', '=', 'mem.odd_type_id');
                    $join->on('em.is_main', '=', 'mem.is_main');
                    $join->on('em.market_flag', '=', 'mem.market_flag');
                })
                ->distinct()
                ->where('mem.master_event_unique_id', $masterEventMarket->master_event_unique_id)
                ->where('mem.odd_type_id', $masterEventMarket->odd_type_id)
                ->where('em.market_flag', $masterEventMarket->market_flag)
                ->where('em.provider_id', $userProvider->id)
                ->get(
                    [
                        'mem.master_event_market_unique_id',
                        'em.odd_label',
                        'em.is_main'
                    ]
                );

            $spreads = [];

            foreach ($getOtherMarkets AS $row) {
                $spreads[] = [
                    'market_id' => $row->master_event_market_unique_id,
                    'points'    => $row->odd_label,
                    'is_main'   => $row->is_main
                ];
            }

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
                'spreads'       => $spreads,
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
        DB::beginTransaction();

        try {
            $swt          = app('swoole');
            $topics       = $swt->topicTable;
            $payloadsSwt  = $swt->payloadsTable;
            $betType      = "";
            $return       = "";
            $returnCode   = 200;
            $prevStake    = 0;
            $orderIds     = [];
            $incrementIds = [
                'id'      => [],
                'payload' => [],
            ];

            foreach ($request->markets AS $row) {
                $betType      = $request->betType;
                $baseCurrency = Currency::where('code', 'CNY')->first();
                $userDetails  = User::find(auth()->user()->id);
                $userCurrency = $userDetails->currency_id;
                $isUserVIP    = false;
                // $isUserVIP = $userDetails->is_vip; /** TO DO: Uncomment after running migration script */
                $userProvider = UserProviderConfiguration::where('user_id', auth()->user()->id);
                $percentage   = 0;
                $alias        = "";
                $exchangeRate = 1;

                if ($baseCurrency->id != $userCurrency) {
                    $exchangeRate = ExchangeRate::where('from_currency_id', $baseCurrency->id)
                        ->where('to_currency_id', $userCurrency)
                        ->first()
                        ->exchange_rate;
                }

                if ($userProvider->exists()) {
                    $userProvider = $userProvider->where('provider_id', $row['provider_id'])
                        ->first();

                    if ($userProvider->active) {
                        $percentage   = $userProvider->punter_percentage;
                        $alias        = $userProvider->alias;
                        $userProvider = $userProvider->provider_id;
                    } else {
                        if ($betType == "BEST_PRICE") {
                            return response()->json([
                                'status'      => false,
                                'status_code' => 400,
                                'message'     => trans('generic.bad-request')
                            ], 400);
                        }

                        if ($betType == "FAST_BET") {
                            continue;
                        }
                    }
                } else {
                    $userProvider = Provider::find($row['provider_id']);

                    if ($userProvider->is_enabled) {
                        $percentage   = $userProvider->punter_percentage;
                        $alias        = $userProvider->alias;
                        $userProvider = $userProvider->id;
                    } else {
                        if ($betType == "BEST_PRICE") {
                            return response()->json([
                                'status'      => false,
                                'status_code' => 400,
                                'message'     => trans('generic.bad-request')
                            ], 400);
                        }

                        if ($betType == "FAST_BET") {
                            continue;
                        }
                    }
                }

                /** TO DO: Wallet Balance Sufficiency Check */
                $userWallet = UserWallet::where('user_id', auth()->user()->id);

                if (!$userWallet->exists()) {
                    return response()->json([
                        'status'      => false,
                        'status_code' => 404,
                        'message'     => trans('generic.not-found') . ": User Wallet Not Found"
                    ], 404);
                }

                $userBalance = $userWallet->first()->balance * $exchangeRate;

                if ($userBalance < ($request->stake * $exchangeRate)) {
                    return response()->json([
                        'status'      => false,
                        'status_code' => 400,
                        'message'     => trans('generic.bad-request') . ": Insufficient Wallet Balance"
                    ], 400);
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
                    ->where('mem.master_event_market_unique_id', $request->market_id)
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

                if (!$query) {
                    return response()->json([
                        'status'      => false,
                        'status_code' => 404,
                        'message'     => trans('generic.not-found')
                    ], 404);
                }

                if ($prevStake == 0) {
                    $payloadStake = $request->stake < $row['max'] ? $request->stake : $row['max'];
                } else {
                    $payloadStake = $prevStake < $row['max'] ? $prevStake : $row['max'];
                }

                $actualStake = $payloadStake / ($percentage / 100);

                if ($request->betType == "BEST_PRICE") {
                    $prevStake = $request->stake - $row['max'];
                }

                if ($payloadStake < $row['min']) {
                    return response()->json([
                        'status'      => false,
                        'status_code' => 400,
                        'message'     => trans('generic.bad-request')
                    ], 400);
                }

                $orderId = uniqid();

                /** ROUNDING UP TO NEAREST 50 */
                $actualStake = $actualStake * $exchangeRate;
                $ceil        = ceil($actualStake);
                $last2       = substr($ceil, -2);

                if (($last2 >= 0) && ($last2 <= 50)) {
                    $actualStake = substr($ceil, 0, -2) . '50';
                } else if ($last2 == 0) {
                    $actualStake = $ceil;
                } else if ($last2 > 50) {
                    $actualStake = substr($ceil, 0, -2) + 1;
                    $actualStake .= '00';
                }

                $payload['user_id']       = auth()->user()->id;
                $payload['provider_id']   = strtolower($alias);
                $payload['odds']          = $row['price'];
                $payload['stake']         = ($payloadStake * $exchangeRate);
                $payload['to_win']        = (($payloadStake * $row['price']) * $exchangeRate);
                $payload['actual_stake']  = $actualStake;
                $payload['actual_to_win'] = $actualStake * $row['price'];
                $payload['market_id']     = $query->bet_identifier;
                $payload['event_id']      = explode('-', $query->master_event_unique_id)[3];
                $payload['score']         = $query->score;
                $payload['orderExpiry']   = $request->orderExpiry;
                $payload['order_id']      = $orderId;
                $payload['sport_id']      = $query->sport_id;

                $incrementIds['payload'][] = $payload;

                $orderIncrementId = Order::create([
                    'user_id'                       => auth()->user()->id,
                    'master_event_market_unique_id' => $request->market_id,
                    'market_id'                     => $query->bet_identifier,
                    'status'                        => "PENDING",
                    'bet_id'                        => "",
                    'bet_selection'                 => "",
                    'provider_id'                   => $row['provider_id'],
                    'sport_id'                      => $query->sport_id,
                    'odds'                          => $row['price'],
                    'stake'                         => ($payloadStake * $exchangeRate),
                    'to_win'                        => (($payloadStake * $row['price']) * $exchangeRate),
                    'actual_stake'                  => $actualStake,
                    'actual_to_win'                 => $actualStake * $row['price'],
                    'settled_date'                  => "",
                    'reason'                        => "",
                    'profit_loss'                   => 0.00,
                    'order_expiry'                  => $request->orderExpiry,
                ])->id;

                $incrementIds['id'][] = $orderIncrementId;

                OrderLogs::create([
                    'user_id'       => auth()->user()->id,
                    'provider_id'   => $row['provider_id'],
                    'sport_id'      => $query->sport_id,
                    'bet_id'        => "",
                    'bet_selection' => "",
                    'status'        => "PENDING",
                    'settled_date'  => "",
                    'reason'        => "",
                    'profit_loss'   => 0.00,
                    'order_id'      => $orderIncrementId,
                ]);

                userWalletTransaction(auth()->user()->id, 'PLACE_BET', ($payloadStake * $exchangeRate));

                if ($request->betType == "FAST_BET") {
                    if ($prevStake == 0) {
                        $prevStake = $request->stake - $payloadStake;
                    } else {
                        $prevStake = $prevStake - $payloadStake;
                    }
                }

                $topicsId = implode(':', [
                    "userId:" . auth()->user()->id,
                    "unique:" . $orderId,
                ]);

                if (!$topics->exists($topicsId)) {
                    $topics->set($topicsId, [
                        'user_id'    => auth()->user()->id,
                        'topic_name' => "order-" . $orderId
                    ]);
                }

                $orderIds[] = $orderId;
            }

            if ($betType == "BEST_PRICE") {
                $return     = $prevStake > 0 ? trans('game.bet.best-price.continue') : trans('game.bet.best-price.success');
                $returnCode = $prevStake > 0 ? 210 : 200;
            }

            if ($betType == "FAST_BET") {
                $return     = $prevStake > 0 ? trans('game.bet.fast-bet.continue') : trans('game.bet.fast-bet.success');
                $returnCode = $prevStake > 0 ? 210 : 200;
            }

            DB::commit();

            for ($i = 0; $i < count($incrementIds['id']); $i++) {
                $requestId = Str::uuid() . "-" . $incrementIds['id'][$i];
                $requestTs = self::milliseconds();
                $payload   = [
                    'request_uid' => $requestId,
                    'request_ts'  => $requestTs,
                    'sub_command' => 'place',
                    'command'     => 'bet'
                ];

                $payload['data'] = [
                    'provider'     => $incrementIds['payload'][$i]['provider_id'],
                    'sport'        => $incrementIds['payload'][$i]['sport_id'],
                    'stake'        => $incrementIds['payload'][$i]['actual_stake'],
                    'odds'         => $incrementIds['payload'][$i]['odds'],
                    'market_id'    => $incrementIds['payload'][$i]['market_id'],
                    'event_id'     => $incrementIds['payload'][$i]['event_id'],
                    'score'        => $incrementIds['payload'][$i]['score'],
                    'username'     => ProviderAccount::getProviderAccount($incrementIds['payload'][$i]['actual_stake'], $isUserVIP),
                ];

                $payloadsSwtId = implode(':', [
                    "place-bet-" . $incrementIds['id'][$i],
                    "uId:"       . $incrementIds['payload'][$i]['user_id'],
                    "mId:"       . $incrementIds['payload'][$i]['market_id'],
                    "oId:"       . $incrementIds['payload'][$i]['order_id']
                ]);

                if (!$payloadsSwt->exists($payloadsSwtId)) {
                    $payloadsSwt->set($payloadsSwtId, [ 'payload' => json_encode($payload) ]);
                }
            }

            return response()->json([
                'status'      => true,
                'status_code' => $returnCode,
                'data'        => $return,
                'order_id'    => $orderIds,
            ], $returnCode);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    public function getBetSlipLogs(string $memUID)
    {
        try {
            $data = [];

            $view = DB::table('bet_slip_logs')
                ->where(function ($cond) {
                    $cond->where('user_id', 0)
                        ->orWhere('user_id', auth()->user()->id);
                })
                ->where('memuid', $memUID)
                ->orderBy('timestamp', 'desc')
                ->get();

            foreach ($view AS $row) {
                $data[$row->timestamp][$row->log_type][$row->provider] = [
                    'description' => trans('game.bet_slip_logs.' . strtolower($row->log_type)),
                    'message'     => $row->message,
                    'data'        => $row->data
                ];
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $data,
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
