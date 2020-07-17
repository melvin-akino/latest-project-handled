<?php

namespace App\Http\Controllers;

use App\Facades\SwooleHandler;
use App\Jobs\KafkaPush;

use App\Models\{
    Currency,
    ExchangeRate,
    Game,
    MasterEvent,
    MasterEventMarket,
    MasterEventMarketLog,
    OddType,
    Provider,
    Sport,
    UserConfiguration,
    UserProviderConfiguration,
    Order,
    OrderLogs,
    Timezones,
    UserWallet,
    ProviderAccountOrder
};
use App\Models\CRM\{
    ProviderAccount
};
use Illuminate\Http\Request;
use Illuminate\Support\{
    Facades\DB,
    Str
};
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OrdersController extends Controller
{
    /**
     * Get all orders made by the user using the parameters below
     *
     * @param Request $request
     * @return json
     */
    public function myOrders(Request $request)
    {
        try {
            $conditions    = [];
            $userTz        = "Etc/UTC";
            $getUserConfig = UserConfiguration::getUserConfig(auth()->user()->id)
                ->where('type', 'timezone')
                ->first();

            if (!is_null($getUserConfig)) {
                $userTz = Timezones::find($getUserConfig->value)->name;
            }

            !empty($request->status) ? !($request->status == "All") ? $conditions[] = [
                'status',
                $request->status
            ] : null : null;

            !empty($request->created_from) ? $conditions[] = ['orders.created_at', '>=', $request->created_from] : null;
            !empty($request->created_to) ? $conditions[] = [
                'orders.created_at',
                '<=',
                $request->created_to
            ] : !empty($request->created_from) ? ['orders.created_at', '<=', now()] : null;

            !empty($request->settled_from) ? $conditions[] = ['settled_date', '>=', $request->settled_from] : null;
            !empty($request->settled_to) ? $conditions[] = [
                'settled_date',
                '<=',
                $request->settled_to
            ] : !empty($request->settled_to) ? ['settled_date', '<=', now()] : null;

            //Pagination part
            $page        = $request->has('page') ? $request->get('page') : 1;
            $limit       = $request->has('limit') ? $request->get('limit') : 25;
            $myAllOrders = Order::countAllOrders();

            if (!empty($myAllOrders)) {
                $myOrders = Order::getAllOrders($conditions, $page, $limit);

                foreach ($myOrders as $myOrder) {
                    $score  = explode(' - ', $myOrder->score);

                    $data['orders'][] = [
                        'order_id'      => $myOrder->id,
                        'bet_id'        => $myOrder->ml_bet_identifier,
                        'bet_selection' => nl2br($myOrder->bet_selection),
                        'provider'      => strtoupper($myOrder->alias),
                        'event_id'      => $myOrder->master_event_unique_id,
                        'market_id'     => $myOrder->master_event_market_unique_id,
                        'odds'          => $myOrder->odds,
                        'stake'         => $myOrder->stake,
                        'towin'         => $myOrder->to_win,
                        'created'       => Carbon::createFromFormat("Y-m-d H:i:s", $myOrder->created_at, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s"),
                        'settled'       => $myOrder->settled_date == "" ? "" : Carbon::createFromFormat("Y-m-d H:i:sO", $myOrder->settled_date, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s"),
                        'pl'            => $myOrder->profit_loss,
                        'status'        => $myOrder->status,
                        'score'         => $myOrder->score,
                        'home_score'    => $score[0],
                        'away_score'    => $score[1],
                        'odd_type_id'   => $myOrder->odd_type_id,
                        'points'        => $myOrder->odd_label,
                        'reason'        => $myOrder->reason
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
            Log::error($e->getMessage());
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
     * @param string $memUID
     * @return json
     */
    public function getEventMarketsDetails(string $memUID)
    {
        try {
            $userTz        = "Etc/UTC";
            $getUserConfig = UserConfiguration::getUserConfig(auth()->user()->id)
                ->where('type', 'timezone')
                ->first();

            if (!is_null($getUserConfig)) {
                $userTz = Timezones::find($getUserConfig->value)->name;
            }

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
                'master_event_id'
            ]);

            $masterEvent = DB::table('master_events as me')
                ->where('me.id', $masterEventMarket->master_event_id)
                ->join('master_leagues as ml', 'ml.id', 'me.master_league_id')
                ->join('master_teams as ht', 'ht.id', 'me.master_team_home_id')
                ->join('master_teams as at', 'at.id', 'me.master_team_away_id')
                ->select([
                    'ml.name as league_name',
                    'ht.name as home_team_name',
                    'at.name as away_team_name',
                    'master_event_unique_id',
                    'game_schedule',
                    'ref_schedule',
                    'running_time',
                    'score',
                    'home_penalty',
                    'away_penalty',
                    'me.sport_id'
                ]);

            if (!$masterEvent->exists()) {
                return response()->json([
                    'status'      => false,
                    'status_code' => 404,
                    'message'     => trans('generic.not-found')
                ], 404);
            }

            $masterEvent = $masterEvent->first();

            $getOtherMarkets = Game::getOtherMarketSpreadDetails([
                'odd_type_id'     => $masterEventMarket->odd_type_id,
                'master_event_id' => $masterEventMarket->master_event_id,
                'market_flag'     => $masterEventMarket->market_flag,
                'providers'       => UserProviderConfiguration::getProviderIdList(auth()->user()->id),
                'game_schedule'   => $masterEvent->game_schedule
            ]);

            $spreads          = [];
            $duplicateHandler = [];

            foreach ($getOtherMarkets AS $row) {
                if (!in_array($row->odd_label, $duplicateHandler)) {
                    $duplicateHandler[] = $row->odd_label;
                    $spreads[]          = [
                        'market_id' => $row->master_event_market_unique_id,
                        'odds'      => $row->odds,
                        'points'    => $row->odd_label,
                        'is_main'   => $row->is_main
                    ];
                }
            }

            $eventBets = Order::getOrdersByEvent($masterEvent->master_event_unique_id)->count();

            $hasBets = false;
            if($eventBets > 0) {
                $hasBets = true;
            }

            $data = [
                'league_name'   => $masterEvent->league_name,
                'home'          => $masterEvent->home_team_name,
                'away'          => $masterEvent->away_team_name,
                'game_schedule' => $masterEvent->game_schedule,
                'ref_schedule'  => Carbon::createFromFormat("Y-m-d H:i:s", $masterEvent->ref_schedule, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s"),
                'running_time'  => $masterEvent->running_time,
                'score'         => $masterEvent->score,
                'home_penalty'  => $masterEvent->home_penalty,
                'away_penalty'  => $masterEvent->away_penalty,
                'market_flag'   => $masterEventMarket->market_flag,
                'odd_type'      => OddType::getTypeByID($masterEventMarket->odd_type_id),
                'sport'         => Sport::getNameByID($masterEvent->sport_id),
                'spreads'       => $spreads,
                'has_bets'      => $hasBets,
                'providers'     => Provider::getProvidersByMemUID($memUID)
            ];

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $data
            ], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
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
     * @param string $memUID
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
            Log::error($e->getMessage());
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
            /**
             * Initiate Swoole Instance.
             *
             * \Swoole\Http\Server $swoole
             */
            $swoole = app('swoole');

            /**
             * Define Swoole Tables.
             *
             * Memory Cache Data Storage
             */
            $currenciesSWT         = $swoole->currenciesTable;
            $exchangeRatesSWT      = $swoole->exchangeRatesTable;
            $orderPayloadsSWT      = $swoole->orderPayloadsTable;
            $ordersSWT             = $swoole->ordersTable;
            $providersSWT          = $swoole->providersTable;
            $topicSWT              = $swoole->topicTable;
            $userProviderConfigSWT = $swoole->userProviderConfigTable;

            /**
             * Initial Variable Declarations
             *
             * @type {String}
             */
            $betType      = "";
            $return       = "";
            $returnCode   = 200;
            $prevStake    = 0;
            $isUserVIP    = auth()->user()->is_vip;
            $orderIds     = [];
            $incrementIds = [];

            foreach ($request->markets AS $row) {
                $betType    = $request->betType;
                $mlBetId    = generateMLBetIdentifier();

                /**
                 * Fetch `userProviderConfig` Swoole Table
                 *
                 * @var  $userProviderConfigSWT  "userId:$userId:pId:$providerId"
                 *                               ['user_id', 'provider_id', 'active', 'punter_percentage']
                 */
                $userProviderPercentage = -1;
                $userProviderConfigKey  = implode(':', [
                    "userId:" . auth()->user()->id,
                    "pId:" . $row['provider_id'],
                ]);

                if ($userProviderConfigSWT->exists($userProviderConfigKey)) {
                    if (!$userProviderConfigSWT[$userProviderConfigKey]['active']) {
                        continue;
                    } else {
                        $userProviderPercentage = $userProviderConfigSWT[$userProviderConfigKey]['punter_percentage'];
                    }
                }

                /**
                 * Set Provider details from `providers` Swoole Table
                 *
                 * @var  $providersSWT  "providerAlias:strtolower($providerAlias)"
                 */
                $providerKey = "providerAlias:" . strtolower($row['provider']);
                $providerInfo = [
                    'alias'             => $providersSWT[$providerKey]['alias'],
                    'currency_id'       => $providersSWT[$providerKey]['currency_id'],
                    'is_enabled'        => $providersSWT[$providerKey]['is_enabled'],
                    'punter_percentage' => $providersSWT[$providerKey]['punter_percentage'],
                ];

                /**
                 * Browse `currencies` Swoole Table
                 *
                 * @var  $currenciesSWT  "currencyId:$id:currencyCode:$code"
                 */
                $userCurrencyInfo = [];

                foreach ($currenciesSWT AS $_key => $_row) {
                    if (strpos($_key, 'currencyId:' . auth()->user()->currency_id) !== false) {
                        $userCurrencyInfo = [
                            'id'   => auth()->user()->currency_id,
                            'code' => $currenciesSWT[$_key]['code'],
                        ];

                        break;
                    }
                }

                /**
                 * Browse `currencies` Swoole Table
                 *
                 * @var  $currenciesSWT  "currencyId:$id:currencyCode:$code"
                 */
                $providerCurrencyInfo = [];

                foreach ($currenciesSWT AS $_key => $_row) {
                    if (strpos($_key, 'currencyId:' . $providerInfo['currency_id']) !== false) {
                        $providerCurrencyInfo = [
                            'id'   => $providerInfo['currency_id'],
                            'code' => $currenciesSWT[$_key]['code']
                        ];

                        break;
                    }
                }

                $exchangeRatesKey = implode(":", [
                    "from:" . $userCurrencyInfo['code'],
                    "to:" . $providerCurrencyInfo['code'],
                ]);

                $exchangeRate = [
                    'id'            => $exchangeRatesSWT[$exchangeRatesKey]['id'],
                    'exchange_rate' => $exchangeRatesSWT[$exchangeRatesKey]['exchange_rate'],
                ];

                $percentage = $userProviderPercentage >= 0 ? $userProviderPercentage : $providerInfo['punter_percentage'];

                if ($prevStake == 0) {
                    $payloadStake = $request->stake < $row['max'] ? $request->stake : $row['max'];
                } else {
                    $payloadStake = $prevStake < $row['max'] ? $prevStake : $row['max'];
                }

                $payloadStake *= $exchangeRate['exchange_rate'];

                /** TO DO: Wallet Balance Sufficiency Check */
                $userWallet = UserWallet::where('user_id', auth()->user()->id);

                if (!$userWallet->exists()) {
                    return response()->json([
                        'status'      => false,
                        'status_code' => 404,
                        'message'     => trans('game.bet.errors.wallet_not_found')
                    ], 404);
                }

                $userBalance = $userWallet->first()->balance * $exchangeRate['exchange_rate'];

                if ($userBalance < $payloadStake) {
                    return response()->json([
                        'status'      => false,
                        'status_code' => 400,
                        'message'     => trans('game.bet.errors.insufficient')
                    ], 400);
                }

                $query = Game::getmasterEventByMarketId($request->market_id);

                if (!$query) {
                    return response()->json([
                        'status'      => false,
                        'status_code' => 404,
                        'message'     => trans('generic.not-found')
                    ], 404);
                }

                $actualStake = $payloadStake / ($percentage / 100);

                if ($request->betType == "BEST_PRICE") {
                    $prevStake = $request->stake - $row['max'];
                }

                if (($payloadStake / $exchangeRate['exchange_rate']) < $row['min']) {
                    return response()->json([
                        'status'      => false,
                        'status_code' => 400,
                        'message'     => trans('generic.bad-request')
                    ], 400);
                }

                $orderId     = uniqid();
                $actualStake *= $exchangeRate['exchange_rate'];

                /** ROUNDING UP TO NEAREST 50 */
                $ceil  = ceil($actualStake);
                $last2 = substr($ceil, -2);

                if (($last2 >= 0) && ($last2 <= 50)) {
                    $actualStake = substr($ceil, 0, -2) . '50';
                } else if ($last2 == 0) {
                    $actualStake = $ceil;
                } else if ($last2 > 50) {
                    $actualStake = (int) substr($ceil, 0, -2) + 1;
                    $actualStake .= '00';
                }

                $payload['user_id']          = auth()->user()->id;
                $payload['provider_id']      = strtolower($row['provider']);
                $payload['odds']             = $row['price'];
                $payload['stake']            = $payloadStake;
                $payload['to_win']           = $payloadStake * $row['price'];
                $payload['actual_stake']     = $actualStake;
                $payload['actual_to_win']    = $actualStake * $row['price'];
                $payload['market_id']        = $query->bet_identifier;
                $payload['event_id']         = explode('-', $query->master_event_unique_id)[3];
                $payload['score']            = $query->score;
                $payload['orderExpiry']      = $request->orderExpiry;
                $payload['order_id']         = $orderId;
                $payload['sport_id']         = $query->sport_id;
                $payload['exchange_rate_id'] = $exchangeRate['id'];
                $payload['exchange_rate']    = $exchangeRate['exchange_rate'];
                $incrementIds['payload'][]   = $payload;

                $teamname = $query->market_flag == "HOME" ? $query->master_home_team_name : $query->master_away_team_name;

                $betSelection = implode("\n", [
                    $query->master_home_team_name . " vs " . $query->master_away_team_name,
                    $teamname . " @ " . number_format($row['price'], 2),
                    $query->column_type . " " . $query->odd_label . "(" . $query->score . ")",
                ]);

                $providerAccount = ProviderAccount::getBettingAccount($row['provider_id'], $actualStake, $isUserVIP, $payload['event_id'], $query->odd_type_id, $query->market_flag);

                if (!$providerAccount) {
                    return response()->json([
                        'status'      => false,
                        'status_code' => 404,
                        'message'     => trans('game.bet.errors.no_bookmaker')
                    ], 404);
                }

                $providerAccountUserName = $providerAccount->username;
                $providerAccountId       = $providerAccount->id;
                $masterEventMarket       = MasterEventMarket::where('master_event_market_unique_id', $request->market_id)->first();

                $_orderData = [
                    'master_event_market_id' => $masterEventMarket->id,
                    'market_id'              => $query->bet_identifier,
                    'odds'                   => $row['price'],
                    'odd_label'              => $query->odd_label,
                    'stake'                  => $payloadStake,
                    'actual_stake'           => $actualStake,
                    'score'                  => $query->score,
                    'expiry'                 => $request->orderExpiry,
                    'bet_selection'          => $betSelection,
                ];

                $_exchangeRate = [
                    'id'            => $exchangeRate['id'],
                    'exchange_rate' => $exchangeRate['exchange_rate'],
                ];

                $orderCreation  = ordersCreation(auth()->user()->id, $query->sport_id, $row['provider_id'], $providerAccountId, $_orderData, $_exchangeRate, $mlBetId);
                $orderIncrement = $orderCreation['orders'];
                $orderLogsId    = $orderCreation['order_logs']->id;

                userWalletTransaction(auth()->user()->id, 'PLACE_BET', ($payloadStake), $orderLogsId);

                $updateProvider             = ProviderAccount::find($providerAccountId);
                $updateProvider->updated_at = Carbon::now();
                $updateProvider->save();

                $incrementIds['id'][]               = $orderIncrement->id;
                $incrementIds['created_at'][]       = (string) $orderIncrement->created_at;
                $incrementIds['provider_account'][] = $providerAccountUserName;

                if ($request->betType == "FAST_BET") {
                    if ($prevStake == 0) {
                        $prevStake = $request->stake - $payloadStake;
                    } else {
                        $prevStake = $prevStake - $payloadStake;
                    }
                }

                $topicKey = implode(':', [
                    "userId:" . auth()->user()->id,
                    "unique:" . $orderId,
                ]);

                if (!$topicSWT->exists($topicKey)) {
                    $topicSWT->set($topicKey, [
                        'user_id'    => auth()->user()->id,
                        'topic_name' => "order-" . $orderIncrement->id
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
                    'provider'         => $incrementIds['payload'][$i]['provider_id'],
                    'sport'            => $incrementIds['payload'][$i]['sport_id'],
                    'stake'            => $incrementIds['payload'][$i]['actual_stake'],
                    'odds'             => $incrementIds['payload'][$i]['odds'],
                    'market_id'        => $incrementIds['payload'][$i]['market_id'],
                    'event_id'         => $incrementIds['payload'][$i]['event_id'],
                    'score'            => $incrementIds['payload'][$i]['score'],
                    'username'         => $incrementIds['provider_account'][$i],
                    'created_at'       => $incrementIds['created_at'][$i],
                    'orderExpiry'      => $incrementIds['payload'][$i]['orderExpiry'],
                    'exchange_rate_id' => $incrementIds['payload'][$i]['exchange_rate_id'],
                    'exchange_rate'    => $incrementIds['payload'][$i]['exchange_rate'],
                ];

                $orderPayloadsKey = implode(':', [
                    "place-bet-" . $incrementIds['id'][$i],
                    "uId:" . $incrementIds['payload'][$i]['user_id'],
                    "mId:" . $incrementIds['payload'][$i]['market_id']
                ]);

                if (!$orderPayloadsSWT->exists($orderPayloadsKey)) {
                    $orderPayloadsSWT->set($orderPayloadsKey, [
                        'payload' => json_encode($payload),
                    ]);
                }

                unset($payload['data']['exchange_rate_id']);
                unset($payload['data']['exchange_rate']);

                KafkaPush::dispatch(
                    $incrementIds['payload'][$i]['provider_id'] . env('KAFKA_SCRAPE_ORDER_REQUEST_POSTFIX', '_bet_req'),
                    $payload,
                    $requestId
                );

                $ordersSWT['orderId:' . $incrementIds['id'][$i]]['username']    = $payload['data']['username'];
                $ordersSWT['orderId:' . $incrementIds['id'][$i]]['orderExpiry'] = $payload['data']['orderExpiry'];
                $ordersSWT['orderId:' . $incrementIds['id'][$i]]['created_at']  = $incrementIds['created_at'][$i];
                $ordersSWT['orderId:' . $incrementIds['id'][$i]]['status']      = 'PENDING';

                SwooleHandler::setValue('pendingOrdersWithin30Table', 'orderId:' . $incrementIds['id'][$i], [
                    'user_id'    => $incrementIds['payload'][$i]['user_id'],
                    'id'         => $incrementIds['id'][$i],
                    'created_at' => $incrementIds['created_at'][$i]
                ]);
            }

            return response()->json([
                'status'      => true,
                'status_code' => $returnCode,
                'data'        => $return,
                'order_id'    => $orderIds,
                'created_at'  => Carbon::parse($orderIncrement->created_at)->toDateTimeString()
            ], $returnCode);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());

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

            $view = Game::getBetSlipLogs(auth()->user()->id, $memUID);

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
            Log::error($e->getMessage());
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    public function betMatrixOrders(string $uid)
    {
        try  {
            $userTz        = "Etc/UTC";
            $getUserConfig = UserConfiguration::getUserConfig(auth()->user()->id)
                ->where('type', 'timezone')
                ->first();

            if (!is_null($getUserConfig)) {
                $userTz = Timezones::find($getUserConfig->value)->name;
            }

            $orders = Order::getOrdersByEvent($uid)->get();

            $data = [];
            foreach($orders as $order) {
                $type = '';
                if ($order->odd_type_id == 3 || $order->odd_type_id == 11) {
                    $type = 'HDP';
                    $points = $order->points;
                } else if ($order->odd_type_id == 4 || $order->odd_type_id == 12) {
                    $ouOddLabel = explode(' ', $order->points);
                    $type = $ouOddLabel[0];
                    $points = $ouOddLabel[1];
                }
                $current_score = explode(' - ', $order->score_on_bet);
                $data[] = [
                    'order_id'          => $order->id,
                    'stake'             => $order->stake,
                    'points'            => $points,
                    'odds'              => $order->odds,
                    'type'              => $type,
                    'bet_team'          => $order->market_flag,
                    'team_name'         => $order->market_flag == 'HOME' ? $order->home_team_name : $order->away_team_name,
                    'home_score_on_bet' => $current_score[0],
                    'away_score_on_bet' => $current_score[1],
                    'created_at'        => Carbon::createFromFormat("Y-m-d H:i:s", $order->created_at, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s"),
                ];
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $data,
            ], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
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
