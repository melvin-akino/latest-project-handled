<?php

namespace App\Http\Controllers;

use App\Exceptions\{BadRequestException, NotFoundException};
use App\Facades\{SwooleHandler, WalletFacade, OrderFacade};
use App\Jobs\KafkaPush;
use App\Models\{
    Game,
    EventMarket,
    MasterEventMarketLog,
    OddType,
    Provider,
    Sport,
    UserConfiguration,
    UserProviderConfiguration,
    Order,
    Timezones,
    UserWallet,
    ProviderAccount,
    BlockedLine,
    MasterEvent,
    RetryType,
    Source,
    OrderLogs,
    SystemConfiguration,
    UserWatchlist
};
use Illuminate\Http\Request;
use Illuminate\Support\{
    Facades\DB,
    Facades\Log,
    Facades\Redis,
    Str
};
use Carbon\Carbon;
use SendLogData;
use App\Http\Requests\OrderRequest;
use Exception;

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
            $myAllOrders = Order::countAllOrders();

            $ouLabels    = OddType::where('type', 'LIKE', '%OU%')->pluck('id')->toArray();

            if (!empty($myAllOrders)) {
                $myOrders = Order::getAllOrders($conditions, $page);

                foreach ($myOrders as $myOrder) {
                    if (empty($myOrder->current_score)) {
                        $currentScore = "0 - 0";
                    } else {
                        $currentScore = $myOrder->current_score;
                    }

                    if (!empty($myOrder->settled_date) && !empty($myOrder->final_score)) {
                        $score = explode(" - ", $myOrder->final_score);
                    } else {
                        $score = explode(" - ", $currentScore);
                    }


                    if (strtoupper($myOrder->market_flag) == "DRAW") {
                        $teamname = "DRAW";
                    } else {
                        $objectKey = "master_team_" . strtolower($myOrder->market_flag) . "_name";
                        $teamname  = $myOrder->{$objectKey};
                    }

                    if (in_array($myOrder->odd_type_id, $ouLabels)) {
                        $ou       = explode(' ', $myOrder->odd_label)[0];
                        $teamname = $ou == "O" ? "Over" : "Under";
                        $teamname .= " " . explode(' ', $myOrder->odd_label)[1];
                    }

                    $origBetSelection = explode(PHP_EOL, $myOrder->bet_selection);
                    $betSelection     = implode("\n", [
                        $myOrder->master_team_home_name . " vs " . $myOrder->master_team_away_name,
                        $teamname . " @ " . $myOrder->odds,
                        end($origBetSelection),
                    ]);

                    if (in_array($myOrder->odd_type_id, $ouLabels)) {
                        $lastLineBetSelection = end($origBetSelection);
                        $ouScore = explode('(', $lastLineBetSelection);
                        $betSelection     = implode("\n", [
                            $myOrder->master_team_home_name . " vs " . $myOrder->master_team_away_name,
                            $teamname . " @ " . $myOrder->odds . " (" . $ouScore[1]
                        ]);
                    }

                    $data['orders'][] = [
                        'order_id'      => $myOrder->id,
                        'bet_id'        => $myOrder->ml_bet_identifier,
                        'bet_selection' => nl2br($betSelection),
                        'provider'      => strtoupper($myOrder->alias),
                        'event_id'      => $myOrder->master_event_unique_id,
                        'market_id'     => $myOrder->master_event_market_unique_id,
                        'odds'          => $myOrder->odds,
                        'stake'         => $myOrder->stake,
                        'towin'         => $myOrder->to_win,
                        'created'       => Carbon::createFromFormat("Y-m-d H:i:s", $myOrder->created_at, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s"),
                        'settled'       => empty($myOrder->settled_date) ? "" : Carbon::createFromFormat("Y-m-d H:i:sO", $myOrder->settled_date, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s"),
                        'pl'            => empty($myOrder->settled_date) ? 0 : $myOrder->profit_loss,
                        'valid_stake'   => empty($myOrder->settled_date) ? 0 : abs($myOrder->profit_loss),
                        'status'        => $myOrder->status,
                        'score'         => empty($myOrder->settled_date) ? $currentScore : $myOrder->final_score,
                        'home_score'    => $score[0],
                        'away_score'    => $score[1],
                        'odd_type_id'   => $myOrder->odd_type_id,
                        'points'        => $myOrder->odd_label,
                        'reason'        => (!empty($myOrder->multiline_error)) ?  $myOrder->multiline_error :  $myOrder->reason
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
            $toLogs = [
                "class"       => "OrdersController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

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

            $masterEventMarket = Order::getEventMarketBetSlipDetails($memUID);

            if (!$masterEventMarket->exists()) {
                $toLogs = [
                    "class"       => "OrdersController",
                    "message"     => trans('orders-related.master-event-market-404'),
                    "module"      => "API_ERROR",
                    "status_code" => 404,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'      => false,
                    'status_code' => 404,
                    'message'     => trans('orders-related.master-event-market-404')
                ], 404);
            }

            $masterEventMarket = $masterEventMarket->first();

            $masterEvent = Order::getEventBetSlipDetails($masterEventMarket->master_event_id);

            if (!$masterEvent->exists()) {
                $toLogs = [
                    "class"       => "OrdersController",
                    "message"     => trans('orders-related.master-event-404'),
                    "module"      => "API_ERROR",
                    "status_code" => 404,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'      => false,
                    'status_code' => 404,
                    'message'     => trans('orders-related.master-event-404')
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

            if (!empty($getOtherMarkets)) {
                foreach ($getOtherMarkets as $row) {
                    if (!in_array($row->odd_label, $duplicateHandler)) {
                        $duplicateHandler[] = $row->odd_label;
                        $spreads[]          = [
                            'market_id'   => $row->mem_uid,
                            'odds'        => $row->odds,
                            'points'      => $row->odd_label,
                            'is_main'     => $row->is_main,
                            'provider_id' => $row->provider_id
                        ];
                    }
                }
            } else {
                $toLogs = [
                    "class"       => "OrdersController",
                    "message"     => trans('orders-related.other-market-404'),
                    "module"      => "API_ERROR",
                    "status_code" => 404,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'      => false,
                    'status_code' => 404,
                    'message'     => trans('orders-related.other-market-404')
                ], 404);
            }

            $eventBets = Order::getOrdersByEvent($masterEvent->master_event_unique_id, true)->count();

            $hasBets = false;
            if ($eventBets > 0) {
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
                'providers'     => Provider::getProvidersByMemUID($memUID),
                'user_status'   => auth()->user()->status
            ];

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $data
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "OrdersController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

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
            DB::beginTransaction();

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
            $providersSWT          = $swoole->providersTable;
            $topicSWT              = $swoole->topicTable;
            $userProviderConfigSWT = $swoole->userProviderConfigTable;
            $remainingStake        = null;

            /**
             * Initial Variable Declarations
             *
             * @type {String}
             */
            $minPrice     = null;
            $betType      = "";
            $return       = "";
            $returnCode   = 200;
            $prevStake    = 0;
            $isUserVIP    = auth()->user()->is_vip;
            $orderIds     = [];
            $incrementIds = [];
            $colMinusOne  = OddType::whereIn('type', ['1X2', 'HT 1X2', 'OE'])->pluck('id')->toArray();

            foreach ($request->markets as $row) {
                if (is_null($minPrice) || $minPrice > $row['min']) {
                    $minPrice = $row['min'];
                }

                $betType = $request->betType;
                $mlBetId = generateMLBetIdentifier();

                /**
                 * Fetch `userProviderConfig` Swoole Table
                 *
                 * @var  $userProviderConfigSWT "userId:$userId:pId:$providerId"
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

                if (empty($userProviderPercentage)) {
                    $toLogs = [
                        "class"       => "OrdersController",
                        "message"     => trans('generic.bad-request'),
                        "module"      => "API_ERROR",
                        "status_code" => 400,
                    ];
                    monitorLog('monitor_api', 'error', $toLogs);

                    throw new BadRequestException(trans('generic.bad-request'));
                }

                /**
                 * Set Provider details from `providers` Swoole Table
                 *
                 * @var  $providersSWT "providerAlias:strtolower($providerAlias)"
                 */
                $providerKey  = "providerAlias:" . strtolower($row['provider']);
                $providerInfo = [
                    'alias'             => $providersSWT[$providerKey]['alias'],
                    'currency_id'       => $providersSWT[$providerKey]['currency_id'],
                    'is_enabled'        => $providersSWT[$providerKey]['is_enabled'],
                    'punter_percentage' => $providersSWT[$providerKey]['punter_percentage']
                ];

                /**
                 * Browse `currencies` Swoole Table
                 *
                 * @var  $currenciesSWT "currencyId:$id:currencyCode:$code"
                 */
                $userCurrencyInfo = [];

                foreach ($currenciesSWT as $_key => $_row) {
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
                 * @var  $currenciesSWT "currencyId:$id:currencyCode:$code"
                 */
                $providerCurrencyInfo = [];

                foreach ($currenciesSWT as $_key => $_row) {
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

                if (is_null($remainingStake)) {
                    $remainingStake = $request->stake;
                }

                if ($prevStake == 0) {
                    $payloadStake = $request->stake < $row['max'] ? $request->stake : $row['max'];
                } else {
                    $payloadStake = $prevStake < $row['max'] ? $prevStake : $row['max'];
                }

                $walletToken = SwooleHandler::getValue('walletClientsTable', 'ml-users')['token'];
                $userBalance = WalletFacade::getBalance($walletToken, auth()->user()->uuid, $userCurrencyInfo['code']);

                if (empty($userBalance) || array_key_exists('error', $userBalance) || !array_key_exists('status_code', $userBalance) || $userBalance->status_code != 200) {
                    $toLogs = [
                        "class"       => "OrdersController",
                        "message"     => trans('game.wallet-api.error.user'),
                        "module"      => "API_ERROR",
                        "status_code" => 404,
                    ];
                    monitorLog('monitor_api', 'error', $toLogs);

                    throw new BadRequestException(trans('game.wallet-api.error.user'));
                }

                if ($userBalance->data->balance < $payloadStake) {
                    $toLogs = [
                        "class"       => "OrdersController",
                        "message"     => trans('game.bet.errors.insufficient'),
                        "module"      => "API_ERROR",
                        "status_code" => 400,
                    ];
                    monitorLog('monitor_api', 'error', $toLogs);

                    throw new BadRequestException(trans('game.bet.errors.insufficient'));
                }

                $query = Game::getMasterEventByMarketId($request->market_id, $row['provider_id']);

                if (!$query) {
                    $toLogs = [
                        "class"       => "OrdersController",
                        "message"     => trans('game.bet.errors.place-bet-event-ended'),
                        "module"      => "API_ERROR",
                        "status_code" => 404,
                    ];
                    monitorLog('monitor_api', 'error', $toLogs);

                    throw new NotFoundException(trans('game.bet.errors.place-bet-event-ended'));
                }

                $actualStake = ($payloadStake * $exchangeRate['exchange_rate']) / ($percentage / 100);
                if ($request->betType == "BEST_PRICE") {
                    $prevStake = $request->stake - $row['max'];
                }

                if ($payloadStake < $row['min']) {
                    $toLogs = [
                        "class"       => "OrdersController",
                        "message"     => trans('game.bet.errors.not-enough-min-stake'),
                        "module"      => "API_ERROR",
                        "status_code" => 400,
                    ];
                    monitorLog('monitor_api', 'error', $toLogs);

                    throw new BadRequestException(trans('game.bet.errors.not-enough-min-stake'));
                }

                $orderId = uniqid();

                /** ROUNDING UP TO NEAREST 50 */
                $ceil  = ceil($actualStake);

                if ($row['provider'] == 'HG') {
                    $last2 = (int) substr($ceil, -2);

                    if (($last2 > 0) && ($last2 <= 50)) {
                        $actualStake = substr($ceil, 0, -2) . '50';
                    } else if ($last2 == 0) {
                        $actualStake = $ceil;
                    } else if ($last2 > 50) {
                        $actualStake  = (int) substr($ceil, 0, -2) + 1;
                        $actualStake .= '00';
                    }
                } else {
                    $actualStake = $ceil;
                }

                $minmaxData = SwooleHandler::getValue('minmaxDataTable', 'minmax-market:' . $query->bet_identifier);

                if ((float) $minmaxData['max'] < (float) $actualStake && $minmaxData) {
                    $actualStake = $minmaxData['max'];
                }

                $payload['user_id']          = auth()->user()->id;
                $payload['provider_id']      = strtolower($row['provider']);
                $payload['odds']             = $row['price'];
                $payload['stake']            = $payloadStake;
                $payload['to_win']           = !in_array($query->odd_type_id, $colMinusOne) ? $payloadStake * $row['price'] : $payloadStake * ($row['price'] - 1);
                $payload['actual_stake']     = $actualStake;
                $payload['actual_to_win']    = !in_array($query->odd_type_id, $colMinusOne) ? $actualStake * $row['price'] : $actualStake * ($row['price'] - 1);
                $payload['market_id']        = $query->bet_identifier;
                $payload['event_id']         = explode('-', $query->master_event_unique_id)[3];
                $payload['score']            = $query->score;
                $payload['orderExpiry']      = $request->orderExpiry;
                $payload['order_id']         = $orderId;
                $payload['sport_id']         = $query->sport_id;
                $payload['exchange_rate_id'] = $exchangeRate['id'];
                $payload['exchange_rate']    = $exchangeRate['exchange_rate'];
                $incrementIds['payload'][]   = $payload;

                $teamname = $query->market_flag == "HOME" ? $query->master_team_home_name : $query->master_team_away_name;

                $betSelection = implode("\n", [
                    $query->master_team_home_name . " vs " . $query->master_team_away_name,
                    $teamname . " @ " . number_format($row['price'], 2),
                    $query->column_type . " " . $query->odd_label . "(" . $query->score . ")",
                ]);

                $blockedLinesParam = [
                    'event_id'    => $query->event_id,
                    'odd_type_id' => $query->odd_type_id,
                    'points'      => $query->odd_label,
                ];

                $_orderData = [
                    'master_event_unique_id'        => $query->master_event_unique_id,
                    'master_event_market_unique_id' => $query->mem_uid,
                    'market_id'                     => $query->bet_identifier,
                    'odds'                          => $row['price'],
                    'odd_label'                     => $request->points,
                    'stake'                         => $payloadStake,
                    'actual_stake'                  => $actualStake,
                    'score'                         => $query->score,
                    'expiry'                        => is_null($request->orderExpiry) ? 30 : $request->orderExpiry,
                    'bet_selection'                 => $betSelection,
                    'odd_type_id'                   => $query->odd_type_id,
                    'market_flag'                   => $query->market_flag,
                    'master_league_name'            => $query->master_league_name,
                    'master_team_home_name'         => $query->master_team_home_name,
                    'master_team_away_name'         => $query->master_team_away_name
                ];

                $_exchangeRate = [
                    'id'            => $exchangeRate['id'],
                    'exchange_rate' => $exchangeRate['exchange_rate'],
                ];

                $orderCreation  = ordersCreation(auth()->user()->id, $query->sport_id, $row['provider_id'], null, $_orderData, $_exchangeRate, $mlBetId, $colMinusOne);
                $orderIncrement = $orderCreation['orders'];
                $orderLogsId    = $orderCreation['order_logs']->id;
                $reason         = "[PLACE_BET][BET PENDING] - transaction for order id " . $orderCreation['orders']->id;

                $userWalletTransaction = userWalletTransaction(auth()->user()->uuid, 'PLACE_BET', ($payloadStake), $userCurrencyInfo['code'], $orderLogsId, $reason);

                if (!$userWalletTransaction) {
                    throw new BadRequestException(trans('game.wallet-api.error.user'));
                }

                $incrementIds['id'][]         = $orderIncrement->id;
                $incrementIds['created_at'][] = (string) $orderIncrement->created_at;

                if ($request->betType == "FAST_BET") {
                    if ($prevStake == 0) {
                        $prevStake = $request->stake - $payloadStake;
                    } else {
                        $prevStake = $prevStake - $payloadStake;
                    }

                    $remainingStake -= $payloadStake;
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
                $notEnoughMessage = strtoupper($userCurrencyInfo['code']) . " " . ($request->stake - $prevStake) . " placed at " . $request->price . "<br>
                " . strtoupper($userCurrencyInfo['code']) . " " . $prevStake . " was not placed. (Does not meet minimum bet)";
                $return     = $prevStake > 0 ? ($prevStake > $minPrice ? trans('game.bet.fast-bet.continue') : $notEnoughMessage) : trans('game.bet.fast-bet.success');
                $returnCode = $prevStake > 0 ? ($prevStake > $minPrice ? 210 : 211) : 200;
            }

            DB::commit();

            for ($i = 0; $i < count($incrementIds['id']); $i++) {
                $orderData       = Order::retryBetData($incrementIds['id'][$i], true);
                $redisExpiration = env('REDIS_TOOL_BALANCE_EXPIRE', 3600);
                $requestId       = Str::uuid() . "-" . $incrementIds['id'][$i];
                $requestTs       = self::milliseconds();
                $payload         = [
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
                    // 'username'         => $incrementIds['provider_account'][$i],
                    'created_at'       => $incrementIds['created_at'][$i],
                    'orderExpiry'      => is_null($incrementIds['payload'][$i]['orderExpiry']) ? 30 : $incrementIds['payload'][$i]['orderExpiry'],
                    'exchange_rate_id' => $incrementIds['payload'][$i]['exchange_rate_id'],
                    'exchange_rate'    => $incrementIds['payload'][$i]['exchange_rate'],
                ];

                retryCacheToRedis($orderData->toArray());

                $toLogs = [
                    "class"       => "OrdersController",
                    "message"     => [
                        "payload_sent" => $payload
                    ],
                    "module"      => "API",
                    "status_code" => 200,
                ];
                monitorLog('monitor_api', 'info', $toLogs);

                $orderSWTKey = 'orderId:' . $incrementIds['id'][$i];
                SwooleHandler::setColumnValue('ordersTable', $orderSWTKey, 'created_at', $incrementIds['created_at'][$i]);
                SwooleHandler::setColumnValue('ordersTable', $orderSWTKey, 'status', 'PENDING');

                SwooleHandler::setValue('pendingOrdersWithinExpiryTable', $orderSWTKey, [
                    'user_id'      => $incrementIds['payload'][$i]['user_id'],
                    'id'           => $incrementIds['id'][$i],
                    'created_at'   => $incrementIds['created_at'][$i],
                    'order_expiry' => (int) $incrementIds['payload'][$i]['orderExpiry']
                ]);
            }

            return response()->json([
                'status'      => true,
                'status_code' => $returnCode,
                'data'        => $return,
                'order_id'    => $orderIncrement->id,
                'created_at'  => Carbon::parse($orderIncrement->created_at)->toDateTimeString()
            ], $returnCode);
        } catch (BadRequestException $e) {
            DB::rollback();

            // if (!empty($exceptionArray)) {
            //     WalletFacade::addBalance($exceptionArray['token'], $exceptionArray['uuid'], $exceptionArray['currency_code'], ($exceptionArray['stake']), "[PLACE_BET][RETURN_STAKE] - Something went wrong: " . $e->getMessage());
            // }

            $toLogs = [
                "class"       => "OrdersController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => 400,
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 400,
                'message'     => $e->getMessage()
            ], 400);
        } catch (NotFoundException $e) {
            DB::rollback();

            // if (!empty($exceptionArray)) {
            //     WalletFacade::addBalance($exceptionArray['token'], $exceptionArray['uuid'], $exceptionArray['currency_code'], ($exceptionArray['stake']), "[PLACE_BET][RETURN_STAKE] - Something went wrong: " . $e->getMessage());
            // }

            $toLogs = [
                "class"       => "OrdersController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => 404,
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 404,
                'message'     => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            DB::rollback();

            // if (!empty($exceptionArray)) {
            //     WalletFacade::addBalance($exceptionArray['token'], $exceptionArray['uuid'], $exceptionArray['currency_code'], ($exceptionArray['stake']), "[PLACE_BET][RETURN_STAKE] - Something went wrong: " . $e->getMessage());
            // }

            $toLogs = [
                "class"       => "OrdersController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    public function getEventDetails(Request $request) {
        try {
            $orderId = $request->order_id;
            $eventMarketDetails = Order::getEventMarketDetails($orderId);

            $eventDetails = Order::getEventDetails($orderId);

            $meUID       = $eventDetails->master_event_unique_id;
            $userId      = auth()->user()->id;
            $topicTable  = app('swoole')->topicTable;
            $singleEvent = true;

            $watchlistEvent = UserWatchlist::where('master_event_id', $eventDetails->master_event_id)->where('user_id', $userId)->exists();

            if($watchlistEvent) {
                $gameDetails = Game::getWatchlistGameDetails($userId, $eventDetails->master_event_id);
            } else {
                $gameDetails = Game::getGameDetails($eventDetails->master_league_id, $eventDetails->game_schedule, $userId, $meUID);
            }

            $otherTransformed   = Game::getOtherMarketsByMasterEventId($meUID);
            $otherMarketDetails = [
                'meUID'       => $meUID,
                'transformed' => $otherTransformed
            ];

            $data = eventTransformation($gameDetails, $userId, $topicTable, 'bet-retry', $otherMarketDetails, $singleEvent);
            $eventData = is_array($data) ? (!empty($data) ? array_values($data)[0] : []) : [];

            if (!$eventMarketDetails || empty($eventData)) {
                return response()->json([
                    'status'      => false,
                    'status_code' => 404,
                    'message'     => 'Event is not found or no longer active'
                ], 404);
            }

            $responseData = [
                'market_common' => $eventMarketDetails->market_common,
                'event' => $eventData
            ];

            $hasActiveEventMarketWithSamePosition = EventMarket::hasActiveEventMarketWithSamePosition($eventMarketDetails);

            if (!$hasActiveEventMarketWithSamePosition) {
                return response()->json([
                    'status'      => false,
                    'status_code' => 200,
                    'message'     => 'No active event market with same position',
                    'data'        => $responseData
                ], 200);
            } else {
                return response()->json([
                    'status'      => true,
                    'status_code' => 200,
                    'data'        => $responseData
                ], 200);
            }
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "OrdersController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    public function postRetryBet(Request $request)
    {
        try {
            $now                        = Carbon::now();
            $maxRetryCount              = SystemConfiguration::getSystemConfigurationValue('RETRY_COUNT');
            $retryExpiry                = SystemConfiguration::getSystemConfigurationValue('RETRY_EXPIRY');
            $retryTypes                 = RetryType::pluck('type')->toArray();
            $orderData                  = Order::retryBetData($request->order_id)->toArray();
            $orderData['retry_type_id'] = RetryType::getIdByType('manual-same-account');
            $manualRetryCount           = OrderLogs::getLogByRetryType($request->order_id, $orderData['retry_type_id'])->count();

            if (in_array($request->retry_type, $retryTypes)) {
                if($manualRetryCount > 0) {
                    $source       = Source::where('source_name', 'LIKE', 'PLACE_BET')->first();
                    $walletToken  = SwooleHandler::getValue('walletClientsTable', 'ml-users')['token'];
                    $user         = auth()->user();
                    $currencyCode = $user->currency()->first()->code;
                    $reason       = "[PLACE_BET][BET PENDING] - transaction for order id " . $request->order_id;
                    $userBalance  = WalletFacade::subtractBalance($walletToken, $user->uuid, trim(strtoupper($currencyCode)), $request->stake, $reason);
                }

                retryCacheToRedis($orderData);

                usleep(2000000);

                return response()->json([
                    'status'      => true,
                    'status_code' => 200,
                    'message'     => "Retrying Bet..."
                ], 200);
            } else {
                $toLogs = [
                    "class"       => "OrdersController",
                    "message"     => trans('generic.bad-request'),
                    "module"      => "API_ERROR",
                    "status_code" => 400,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                throw new BadRequestException(trans('generic.bad-request'));
            }
        } catch (BadRequestException $e) {
            $toLogs = [
                "class"       => "OrdersController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => 400,
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 400,
                'message'     => $e->getMessage()
            ], 400);
        } catch (NotFoundException $e) {
            $toLogs = [
                "class"       => "OrdersController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => 404,
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 404,
                'message'     => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "OrdersController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    public function getBetSlipLogs(string $uid)
    {
        try {
            $userTz        = "Etc/UTC";
            $getUserConfig = UserConfiguration::getUserConfig(auth()->user()->id)
                                ->where('type', 'timezone')
                                ->first();
            if (!is_null($getUserConfig)) {
                $userTz = Timezones::find($getUserConfig->value)->name;
            }
            $orders   = Order::getOrdersByEvent($uid)->get();
            $ouLabels = OddType::where('type', 'ILIKE', '%OU%')->pluck('id')->toArray();
            $oeLabels = OddType::where('type', 'ILIKE', '%OE%')->pluck('id')->toArray();
            $data     = [];
            foreach ($orders as $order) {
                $betTeam       = $order->market_flag == 'HOME' ? $order->home_team_name : $order->away_team_name;
                $oddTypeName   = $order->sport_odd_type_name;
                $oddTypePrefix = stripos($order->odd_type, 'HT ') !== false ? 'HT' : 'FT';
                $points        = $order->points;

                if (in_array($order->odd_type_id, $ouLabels)) {
                    $ouOddLabel  = explode(' ', $order->points);
                    $betTeam     = $ouOddLabel[0] == "O" ? "Over" : "Under";
                    $oddTypeName = $oddTypePrefix . " " . $betTeam;
                    $points      = $ouOddLabel[1];
                }

                if (in_array($order->odd_type_id, $oeLabels)) {
                    $betTeam     = $order->points == "O" ? "Odd" : "Even";
                    $oddTypeName = $oddTypePrefix . " " . $betTeam;
                    $points      = '';
                }

                $data[] = [
                    'order_id'      => $order->id,
                    'stake'         => $order->stake,
                    'odds'          => $order->odds,
                    'points'        => $points,
                    'odd_type_name' => $oddTypeName,
                    'bet_team'      => $betTeam,
                    'score_on_bet'  => $order->score_on_bet,
                    'provider'      => $order->provider,
                    'status'        => $order->status,
                    'created_at'    => Carbon::createFromFormat("Y-m-d H:i:s", $order->created_at, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s"),
                ];
            }
            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $data,
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "OrdersController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    public function betMatrixOrders(Request $request)
    {
        try {
            $userTz        = "Etc/UTC";
            $getUserConfig = UserConfiguration::getUserConfig(auth()->user()->id)
                                ->where('type', 'timezone')
                                ->first();

            if (!is_null($getUserConfig)) {
                $userTz = Timezones::find($getUserConfig->value)->name;
            }

            $eventId  = $request->event_id;
            $marketId = $request->market_id;

            $oddType  = EventMarket::getOddTypeByMemUID($marketId);
            $halfTime = false;

            if(stripos($oddType->type, 'HT') !== false) {
                $halfTime = true;
            }

            $orders     = Order::getOrdersByEvent($eventId, true, $halfTime)->get();
            $ouLabels   = OddType::where('type', 'ILIKE', '%OU%')->pluck('id')->toArray();
            $oeLabels   = OddType::where('type', 'ILIKE', '%OE%')->pluck('id')->toArray();
            $eventScore = array_map('trim', explode('-', MasterEvent::getMasterEvent($eventId)->score));

            $currentScore = [
                'home' => $eventScore[0],
                'away' => $eventScore[1]
            ];

            $data = [];
            foreach ($orders as $order) {
                $type   = '';
                $points = '';
                if (stripos($order->odd_type, 'HDP') !== false) {
                    $type   = 'HDP';
                    $points = $order->points;
                } else if (stripos($order->odd_type, 'OU') !== false) {
                    $ouOddLabel = explode(' ', $order->points);
                    $type       = $ouOddLabel[0];
                    $points     = $ouOddLabel[1];
                } else if(stripos($order->odd_type, '1x2') !== false) {
                    $type = '1x2';
                } else if(stripos($order->odd_type, 'OE') !== false) {
                    $type = $order->points == "O" ? "Odd" : "Even";
                }

                $teamname = $order->market_flag == 'HOME' ? $order->home_team_name : $order->away_team_name;
                $betTeam  = $order->market_flag;

                if (in_array($order->odd_type_id, $ouLabels)) {
                    $ou       = explode(' ', $order->points)[0];
                    $betTeam  = $teamname = $ou == "O" ? "Over" : "Under";
                }

                if (in_array($order->odd_type_id, $oeLabels)) {
                    $betTeam  = $teamname = $order->points == "O" ? "Odd" : "Even";
                }

                $scoreOnBet = array_map('trim', explode('-', $order->score_on_bet));

                if(!empty($order->final_score)) {
                    $finalScore =  array_map('trim', explode('-', $order->final_score));
                    $currentScore['home'] = $finalScore[0];
                    $currentScore['away'] = $finalScore[1];
                }

                $data[]     = [
                    'order_id'          => $order->id,
                    'stake'             => $order->stake,
                    'points'            => $points,
                    'odds'              => $order->odds,
                    'type'              => $type,
                    'odd_type_name'     => $order->sport_odd_type_name,
                    'bet_team'          => $betTeam,
                    'team_name'         => $teamname,
                    'home_score_on_bet' => $scoreOnBet[0],
                    'away_score_on_bet' => $scoreOnBet[1],
                    'created_at'        => Carbon::createFromFormat("Y-m-d H:i:s", $order->created_at, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s"),
                    'final_score'       => $order->final_score,
                    'odd_type'          => $order->odd_type
                ];
            }

            return response()->json([
                'status'        => true,
                'status_code'   => 200,
                'data'          => $data,
                'current_score' => $currentScore
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "OrdersController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    public function receiveMinMaxLog(Request $request)
    {
        SendLogData::MinMax('receiveminmax', $request->payload);
    }

    private static function milliseconds()
    {
        $mt = explode(' ', microtime());

        return bcadd($mt[1], $mt[0], 8);
    }

    public function myOrdersV2(OrderRequest $request)
    {
        return OrderFacade::getOrders($request);
    }

    public function myHistory(OrderRequest $request)
    {
        return OrderFacade::getOrders($request);
    }
}
