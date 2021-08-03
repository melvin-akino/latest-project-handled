<?php

/*
|--------------------------------------------------------------------------
| Custom Helper Functions
|--------------------------------------------------------------------------
|
| Here you can create your own functions depending on your own needs
| and call them anywhere inside your Laravel Project.
|
| Some of the functions created here can still be extended to
| accomodate every developer's needs.
|
| MUST require every developer to include a commented @author tag
| for us to know which one to look for in case of any questions
| and/or misunderstandings. Also, write down developer's name
| should there be any adjustments made with existing helper functions.
|
| Have fun coding!
|
*/

use App\Facades\{WalletFacade, SwooleHandler};
use App\Models\{
    Currency,
    Sport,
    UserConfiguration,
    UserWallet,
    Source,
    Order,
    OrderLogs,
    ProviderAccountOrder,
    Game,
    Timezones,
    UserProviderConfiguration,
    EventScore,
    WalletLedger,
    SystemConfiguration,
    Provider
};
use App\Models\CRM\OrderTransaction;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\{Cookie, Log, Redis};

/* Datatable for CRM admin */

function dataTable(Request $request, $query, $cols = null)
{
    $order = collect($request->input('order')[0]);
    $col   = collect($request->input('columns')[$order->get('column')])->get('data');
    $dir   = $order->get('dir');
    $q     = trim($request->input('search')['value']);
    $len   = $request->input('length');
    $page  = ($request->input('start') / $len) + 1;

    Paginator::currentPageResolver(function () use ($page) {
        return $page;
    });

    $pagin = null;

    if (!empty($q)) {
        $pagin = $query->search($q, $cols)->orderBy($col, $dir)->paginate($len);
    } else {
        $pagin = $query->orderBy($col, $dir)->paginate($len);
    }

    return response()->json([
        "draw"            => intval($request->input('draw')),
        "recordsTotal"    => $pagin->total(),
        "recordsFiltered" => $pagin->total(),
        "data"            => $pagin->items()
    ]);
}

/* end databtable */

/* Swal CRM popup container*/

function swal($title, $html, $type)
{
    $swal = [
        'title' => $title,
        'html'  => $html,
        'type'  => $type
    ];

    return compact('swal');
}

/* End SWal CRM popup container */

/**
 * Delete Cookie by Name
 *
 * @param string $cookieName Illuminate\Support\Facades\Cookie;
 *
 * @author  Kevin Uy
 */
function deleteCookie(string $cookieName)
{
    Cookie::queue(Cookie::forget($cookieName));
}

/**
 * Save Authenticated User's Default Configuration by Type
 *
 * @param int $userId
 * @param string $type
 * @param array|null $data
 * @return  json
 *
 * @author  Kevin Uy, Alex Virtucio
 */
if (!function_exists('setUserDefault')) {
    function setUserDefault(int $userId, string $type, array $data = [])
    {
        $types = [
            'sport',
            'league',
        ];

        if (in_array($type, $types)) {
            switch ($type) {
                case 'sport':
                    UserConfiguration::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'type'    => "DEFAULT_SPORT",
                            'menu'    => 'TRADE',
                        ],
                        [
                            'value' => $data['sport_id']
                        ]
                    );
                    break;

                case 'league':
                    //
                    break;

                    $data = [
                        'status'  => true,
                        'message' => trans('notifications.save.success')
                    ];
            }
        } else {
            $data = [
                'status' => false,
                'error'  => trans('generic.bad-request'),
            ];
        }

        return $data;
    }
}

/**
 * Get Authenticated User's Default Configuration by Type
 *
 * @param int $userId
 * @param string $type
 * @return  $data
 *
 * @author  Kevin Uy
 */
if (!function_exists('getUserDefault')) {
    function getUserDefault(int $userId, string $type)
    {
        $data  = [];
        $types = [
            'sport',
            'league',
            'sort-event'
        ];

        if (in_array($type, $types)) {
            switch ($type) {
                case 'sport':
                    $defaultSport = UserConfiguration::where('type', 'DEFAULT_SPORT')
                                                     ->where('menu', 'TRADE')
                                                     ->where('user_id', $userId);

                    if ($defaultSport->count() == 0) {
                        $defaultSport = Sport::getActiveSports();
                        $sport        = $defaultSport->first()->id;
                    } else {
                        $sport = $defaultSport->first()->value;
                    }

                    $data = [
                        'status'        => true,
                        'default_sport' => $sport,
                    ];
                    break;
                case 'sort-event':
                    $defaultEventSort = UserConfiguration::where('type', 'sort_event')
                                                         ->where('menu', 'trade-page')
                                                         ->where('user_id', $userId);

                    if ($defaultEventSort->count() == 0) {
                        $sort = config('default_config.trade-page.sort_event');
                    } else {
                        $sort = $defaultEventSort->first()->value;
                    }

                    $data = [
                        'status'       => true,
                        'default_sort' => $sort,
                    ];
                    break;
                case 'league':
                    //
                    break;
            }
        } else {
            $data = [
                'status' => false,
                'error'  => trans('generic.bad-request'),
            ];
        }

        return $data;
    }
}

/**
 * Broadcast Emit
 *
 * @params array $content
 *
 * @author Alex Virtucio
 */
if (!function_exists('wsEmit')) {
    function wsEmit($content)
    {
        $server = app('swoole');
        $table  = $server->wsTable;
        foreach ($table as $key => $row) {
            if (strpos($key, 'uid:') === 0 && $server->isEstablished($row['value'])) {
                $server->push($row['value'], json_encode($content));
            }
        }
    }
}

/**
 * Handle User Wallet related Transactions
 *
 * @param int $userId Authenticated User's ID
 * @param string $transactionType 'source_name' from 'source' Database Table
 * @param float $amount Amount from Transaction (MUST already be converted to Application's Base Currency [CNY])
 * @param float $orderLogsId Order Logs ID
 */
if (!function_exists('userWalletTransaction')) {
    function userWalletTransaction($uuid, $transactionType, $amount, $currency, $orderLogsId, $reason)
    {
        try {
            switch ($transactionType) {
                case 'PLACE_BET':
                    $sourceId    = Source::where('source_name', $transactionType)->first()->id;
                    $currencyId  = Currency::where('code', 'LIKE', trim(strtoupper($currency)))->first()->id;
                    $walletToken = SwooleHandler::getValue('walletClientsTable', 'ml-users')['token'];
                    $userBalance = WalletFacade::subtractBalance($walletToken, $uuid, trim(strtoupper($currency)), $amount, $reason);
                    $userId      = User::where('uuid', $uuid)->first()->id;

                    if (!empty($userBalance) && !array_key_exists('error', $userBalance) && array_key_exists('status_code', $userBalance) && $userBalance->status_code == 200) {
                        OrderTransaction::create(
                            [
                                'wallet_ledger_id'    => $userBalance->data->id,
                                'provider_account_id' => 0,
                                'order_logs_id'       => $orderLogsId,
                                'user_id'             => $userId,
                                'source_id'           => $sourceId,
                                'currency_id'         => $currencyId,
                                'reason'              => "Placed Bet",
                                'amount'              => $amount,
                            ]
                        );
                    } else {
                        return false;
                    }
                break;

                /** TO DO: Add more cases for every User Transaction catered by the application */
            }

            return true;
        } catch (Exception $e) {
            Log::error(json_encode(
                [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile() . " @ " . $e->getLine()
                ]
            ));

            return false;
        }
    }
}

/**
 * Generate Sequential Multiline Bet Identifier.
 *
 * @return string
 *
 * @author  Kevin Uy
 */
if (!function_exists('generateMLBetIdentifier')) {
    function generateMLBetIdentifier()
    {
        $server      = app('swoole');
        $swTable     = $server->mlBetIdTable;
        $betId       = $swTable->get('mlBetId')['ml_bet_id'];
        $currentDate = date('Ymd');
        $date        = substr($betId, 2, 8);
        $sequence    = str_pad(((int) substr($betId, -6)) + 1, 6, '0', STR_PAD_LEFT);

        if ($date != $currentDate) {
            $sequence = str_pad(1, 6, '0', STR_PAD_LEFT);
        }

        $data = "ML" . $currentDate . $sequence;

        $swTable->set('mlBetId', [
            'ml_bet_id' => $data,
        ]);

        return $data;
    }
}

if (!function_exists('getMilliseconds')) {
    function getMilliseconds()
    {
        $mt = explode(' ', microtime());
        return bcadd($mt[1], $mt[0], 8);
    }
}

/**
 * Reusable Orders and Orders-related Table Insertion.
 *
 * @param int $userId
 * @param int $sportId
 * @param int $providerId
 * @param int $providerAccountId
 * @param array $orderData ['market_id', 'odds', 'odd_label', 'stake', 'actual_stake', 'score', 'expiry', 'bet_selection']
 * @param array $exchangeRate ['id', 'exchange_rate']
 * @param string $mlBetId
 * @return void
 *
 * @author  Kevin Uy
 */
if (!function_exists('ordersCreation')) {
    function ordersCreation(int $userId, int $sportId, int $providerId, $providerAccountId = null, array $orderData, array $exchangeRate, string $mlBetId, array $colMinusOne = [])
    {
        $order = Order::create([
            'user_id'                       => $userId,
            'master_event_unique_id'        => $orderData['master_event_unique_id'],
            'master_event_market_unique_id' => $orderData['master_event_market_unique_id'],
            'market_id'                     => $orderData['market_id'],
            'status'                        => "PENDING",
            'bet_id'                        => "",
            'bet_selection'                 => $orderData['bet_selection'],
            'provider_id'                   => $providerId,
            'sport_id'                      => $sportId,
            'odds'                          => $orderData['odds'],
            'odd_label'                     => $orderData['odd_label'],
            'stake'                         => $orderData['stake'],
            'to_win'                        => !in_array($orderData['odd_type_id'], $colMinusOne) ? $orderData['stake'] * $orderData['odds'] : $orderData['stake'] * ($orderData['odds'] - 1),
            'settled_date'                  => null,
            'reason'                        => "",
            'profit_loss'                   => 0.00,
            'order_expiry'                  => $orderData['expiry'],
            'provider_account_id'           => $providerAccountId,
            'ml_bet_identifier'             => $mlBetId,
            'score_on_bet'                  => $orderData['score'],
            'odd_type_id'                   => $orderData['odd_type_id'],
            'market_flag'                   => $orderData['market_flag'],
            'final_score'                   => null,
            'master_league_name'            => $orderData['master_league_name'],
            'master_team_home_name'         => $orderData['master_team_home_name'],
            'master_team_away_name'         => $orderData['master_team_away_name']
        ]);

        EventScore::updateOrCreate([
            'master_event_unique_id' => $orderData['master_event_unique_id']
        ], [
            'score' => $orderData['score']
        ]);

        $orderLogs = OrderLogs::create([
            'user_id'       => $userId,
            'provider_id'   => $providerId,
            'sport_id'      => $sportId,
            'bet_id'        => "",
            'bet_selection' => nl2br($orderData['bet_selection']),
            'status'        => "PENDING",
            'settled_date'  => null,
            'reason'        => "",
            'profit_loss'   => 0.00,
            'order_id'      => $order->id,
        ]);

        ProviderAccountOrder::create([
            'order_log_id'       => $orderLogs->id,
            'exchange_rate_id'   => $exchangeRate['id'],
            'actual_stake'       => $orderData['actual_stake'],
            'actual_to_win'      => !in_array($orderData['odd_type_id'], $colMinusOne) ? $orderData['actual_stake'] * $orderData['odds'] : $orderData['actual_stake'] * ($orderData['odds'] - 1),
            'actual_profit_loss' => 0.00,
            'exchange_rate'      => $exchangeRate['exchange_rate'],
        ]);

        return [
            'orders'     => $order,
            'order_logs' => $orderLogs,
        ];
    }
}

if (!function_exists('eventTransformation')) {
    function eventTransformation($transformed, $userId, $topicTable, $type = 'selected', $otherMarketDetails = [], $singleEvent = false)
    {
        $primaryProviderId = Provider::getIdFromAlias(SystemConfiguration::getSystemConfigurationValue('PRIMARY_PROVIDER')->value);

        $oppositeFlag = [
            'HOME' => 'AWAY',
            'AWAY' => 'HOME',
            'DRAW' => 'DRAW'
        ];
        $data     = [];
        $result   = [];
        $userBets = Order::getOrdersByUserId($userId);

        $userConfig    = getUserDefault($userId, 'sort-event')['default_sort'];
        $userTz        = "Etc/UTC";
        $getUserConfig = UserConfiguration::getUserConfig($userId)
                                          ->where('type', 'timezone')
                                          ->first();

        if ($getUserConfig) {
            $userTz = Timezones::find($getUserConfig->value)->name;
        }

        $userProviderIds = UserProviderConfiguration::getProviderIdList($userId);

        foreach ($transformed as $transformed) {
            if (!in_array($transformed->provider_id, $userProviderIds)) {
                continue;
            }

            $hasBet = false;

            if (!empty($userBets)) {
                $userOrderMarkets = array_column($userBets, 'market_id');
                if (in_array($transformed->bet_identifier, $userOrderMarkets)) {
                    $hasBet = true;
                }
            }

            if ($userConfig == 1) {
                $groupIndex = $transformed->master_league_name;
            } else {
                $refSchedule = DateTime::createFromFormat('Y-m-d H:i:s', $transformed->ref_schedule);
                $groupIndex  = $refSchedule->format('[H:i:s]') . ' ' . $transformed->master_league_name;
            }

            if (!SwooleHandler::exists('providerEventsTable', $transformed->event_identifier)) {
                SwooleHandler::setValue('providerEventsTable', $transformed->event_identifier, [
                    'event_identifier'       => $transformed->event_identifier,
                    'master_event_unique_id' => $transformed->master_event_unique_id
                ]);
            }

            if (empty($data[$transformed->master_event_unique_id])) {
                $providersOfEvents    = Game::providersOfEvents($transformed->master_event_id, $userProviderIds, $transformed->game_schedule)->get();
                $eventHasOtherMarkets = Game::checkIfHasOtherMarkets($transformed->master_event_unique_id, $userProviderIds);

                $providerIds = array_map(function($x){ return $x->id; }, $providersOfEvents->toArray());
                if (!in_array($primaryProviderId, $providerIds)) {
                        continue;
                }

                $data[$transformed->master_event_unique_id]["uid"]               = $transformed->master_event_unique_id;
                $data[$transformed->master_event_unique_id]["master_league_id"]  = $transformed->master_league_id;
                $data[$transformed->master_event_unique_id]['sport_id']          = $transformed->sport_id;
                $data[$transformed->master_event_unique_id]['sport']             = $transformed->sport;
                $data[$transformed->master_event_unique_id]['provider_id']       = $transformed->provider_id;
                $data[$transformed->master_event_unique_id]['game_schedule']     = $transformed->game_schedule;
                $data[$transformed->master_event_unique_id]['league_name']       = $transformed->master_league_name;
                $data[$transformed->master_event_unique_id]['running_time']      = $transformed->running_time;
                $data[$transformed->master_event_unique_id]['ref_schedule']      = Carbon::createFromFormat("Y-m-d H:i:s", $transformed->ref_schedule, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s");
                $data[$transformed->master_event_unique_id]['has_bet']           = $hasBet;
                $data[$transformed->master_event_unique_id]['with_providers']    = $providersOfEvents;
                $data[$transformed->master_event_unique_id]['has_other_markets'] = $eventHasOtherMarkets;

                if (in_array($type, ['socket-watchlist', 'watchlist'])) {
                    SwooleHandler::setValue('userWatchlistTable', 'userWatchlist:' . $userId . ':masterEventUniqueId:' . $transformed->master_event_unique_id, [
                        'value' => true
                    ]);
                }
            }

            if (empty($data[$transformed->master_event_unique_id]['home'])) {
                $data[$transformed->master_event_unique_id]['home'] = [
                    'name'    => $transformed->master_team_home_name,
                    'score'   => empty($transformed->score) ? '' : array_values(explode(' - ', $transformed->score))[0],
                    'redcard' => $transformed->home_penalty
                ];
            }
            if (empty($data[$transformed->master_event_unique_id]['away'])) {
                $data[$transformed->master_event_unique_id]['away'] = [
                    'name'    => $transformed->master_team_away_name,
                    'score'   => empty($transformed->score) ? '' : array_values(explode(' - ', $transformed->score))[1],
                    'redcard' => $transformed->home_penalty
                ];
            }

            if (!empty($transformed->type)) {
                if (
                    empty($data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]) ||
                    $data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['odds'] < (double) $transformed->odds ||
                    $transformed->provider_id == $primaryProviderId
                ) {
                    if (in_array($transformed->odd_type_id, [3, 11]))
                    {
                        if (!empty($data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$oppositeFlag[$transformed->market_flag]]['points']) &&
                            abs($data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$oppositeFlag[$transformed->market_flag]]['points']) != abs($transformed->odd_label) &&
                            $transformed->provider_id != $primaryProviderId
                        ) {
                            Log::info("Skip if not the same points for comparison for " . $transformed->market_common . " for master event unique id " . $transformed->master_event_unique_id);
                            continue;
                        }
                    }

                    if (in_array($transformed->odd_type_id, [3, 11]) &&
                        !empty($data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['points']) &&
                        ($data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['points']) != ($transformed->odd_label) &&
                        $transformed->provider_id != $primaryProviderId
                    ) {
                        Log::info("Skip if not the same points for comparison for " . $transformed->market_common . " for master event unique id " . $transformed->master_event_unique_id);
                        continue;
                    }

                    if (in_array($transformed->odd_type_id, [4, 12]) &&
                        !empty($data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$oppositeFlag[$transformed->market_flag]]['points']) &&
                        substr($data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$oppositeFlag[$transformed->market_flag]]['points'], 1) != substr($transformed->odd_label, 1) &&
                        $transformed->provider_id != $primaryProviderId
                        ) {
                        Log::info("Skip if not the same points for comparison for " . $transformed->market_common . " for master event unique id " . $transformed->master_event_unique_id);
                        continue;
                    }

                    if (in_array($transformed->odd_type_id, [4, 12]) &&
                        !empty($data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['points']) &&
                        substr($data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['points'], 1) != substr($transformed->odd_label, 1) &&
                        $transformed->provider_id != $primaryProviderId
                        ) {
                        Log::info("Skip if not the same points for comparison for " . $transformed->market_common . " for master event unique id " . $transformed->master_event_unique_id);
                        continue;
                    }

                    $data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['odds']           = !empty($transformed->master_event_market_unique_id) && empty($transformed->is_market_empty) ? (double) $transformed->odds : "";
                    $data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['provider_alias'] = $transformed->alias;
                    $data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['market_common']  = $transformed->market_common;

                    if (!empty($transformed->odd_label)) {
                        if (empty($transformed->is_market_empty)) {
                            $data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['points'] = $transformed->odd_label;
                        } else {
                            $data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['points'] = "";
                        }
                    }

                    if (!SwooleHandler::exists('providerEventMarketsTable', $transformed->event_identifier . ":" . $transformed->type . $transformed->market_flag . $transformed->odd_label)) {
                        SwooleHandler::setValue('providerEventMarketsTable', $transformed->event_identifier . ":" . $transformed->type . $transformed->market_flag . $transformed->odd_label, [
                            'bet_identifier' => $transformed->bet_identifier,
                            'type'           => $transformed->type,
                            'market_flag'    => $transformed->market_flag,
                            'points'         => $transformed->odd_label,
                            'mem_uid'        => $transformed->master_event_market_unique_id
                        ]);
                    }
                }

                if (
                    empty($data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['market_id']) ||
                    (
                        !empty($data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['market_id']) &&
                        $transformed->provider_id == $primaryProviderId
                    )

                ) {
                    $data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['mem_uid_from_provider'] = $transformed->provider_id;
                    $data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['market_id'] = !empty($transformed->master_event_market_unique_id) && empty($transformed->is_market_empty) ? $transformed->master_event_market_unique_id : "";
                    if (SwooleHandler::exists('providerEventMarketsTable', $transformed->event_identifier . ":" . $transformed->type . $transformed->market_flag . $transformed->odd_label)) {
                        SwooleHandler::setColumnValue('providerEventMarketsTable', $transformed->event_identifier . ":" . $transformed->type . $transformed->market_flag . $transformed->odd_label, 'mem_uid', $data[$transformed->master_event_unique_id]['market_odds']['main'][$transformed->type][$transformed->market_flag]['market_id']);
                    }
                }

                if ($otherMarketDetails && $transformed->master_event_unique_id == $otherMarketDetails['meUID']) {
                    $otherTransformed = $otherMarketDetails['transformed'];
                    $otherData        = [];
                    array_map(function ($otherTransformed) use (&$otherData, $userProviderIds, $data, $otherMarketDetails) {
                        if (
                            !empty($data[$otherMarketDetails['meUID']]['market_odds']['main'][$otherTransformed->type][$otherTransformed->market_flag]['points']) &&
                            $data[$otherMarketDetails['meUID']]['market_odds']['main'][$otherTransformed->type][$otherTransformed->market_flag]['points'] == $otherTransformed->odd_label) {
                            return $otherTransformed;
                        }

                        if (!in_array($otherTransformed->provider_id, $userProviderIds)) {
                            return $otherTransformed;
                        }
                        if (!empty($otherTransformed->odd_label)) {
                            $otherData[$otherTransformed->market_event_identifier][$otherTransformed->odd_label . $otherTransformed->type . $otherTransformed->market_flag] = [
                                'odds'                    => (double) $otherTransformed->odds,
                                'market_id'               => $otherTransformed->master_event_market_unique_id,
                                'points'                  => $otherTransformed->odd_label,
                                'master_event_identifier' => $otherTransformed->market_event_identifier,
                                'odd_type'                => $otherTransformed->type,
                                'market_flag'             => $otherTransformed->market_flag,
                                'market_event_identifier' => $otherTransformed->market_event_identifier,
                                'provider_alias'          => $otherTransformed->alias,
                                'provider_id'             => $otherTransformed->provider_id,
                                'bet_identifier'          => $otherTransformed->bet_identifier,
                                'event_identifier'        => $otherTransformed->event_identifier,
                                'market_common'           => $otherTransformed->market_common,
                            ];
                        }
                    }, $otherTransformed->toArray());

                    $otherResult = [];
                    $otherValues = [];
                    foreach ($otherData as $masterEventIdentifier) {
                        foreach ($masterEventIdentifier as $k => $d) {
                            if (empty($otherValues[$d['odd_type'] . $d['market_flag'] . $d['points']])) {
                                $otherResult[$d['market_event_identifier']][$d['odd_type']][$d['market_flag']]['market_id']             = $d['market_id'];
                                $otherResult[$d['market_event_identifier']][$d['odd_type']][$d['market_flag']]['mem_uid_from_provider'] = $d['provider_id'];

                                $otherResult[$d['market_event_identifier']][$d['odd_type']][$d['market_flag']]['points']                = $d['points'];
                                $otherResult[$d['market_event_identifier']][$d['odd_type']][$d['market_flag']]['provider_alias']        = $d['provider_alias'];
                                $otherResult[$d['market_event_identifier']][$d['odd_type']][$d['market_flag']]['odds']                  = $d['odds'];
                                $otherResult[$d['market_event_identifier']][$d['odd_type']][$d['market_flag']]['market_common']         = $d['market_common'];
                                $otherValues[$d['odd_type'] . $d['market_flag'] . $d['points']]                                         = $d['market_event_identifier'];

                                if (!SwooleHandler::exists('providerEventMarketsTable', $d['event_identifier'] . ":" . $d['odd_type'] . $d['market_flag'] . $d['points'])) {
                                    SwooleHandler::setValue('providerEventMarketsTable', $d['event_identifier'] . ":" . $d['odd_type'] . $d['market_flag'] . $d['points'], [
                                        'bet_identifier' => $d['bet_identifier'],
                                        'type'           => $d['odd_type'],
                                        'market_flag'    => $d['market_flag'],
                                        'points'         => $d['points'],
                                        'mem_uid'        => $d['market_id']
                                    ]);
                                }
                            }

                            else {
                                $key = $otherValues[$d['odd_type'] . $d['market_flag'] . $d['points']];
                                if (
                                    !empty($otherResult[$key][$d['odd_type']][$d['market_flag']]) &&
                                    $otherResult[$key][$d['odd_type']][$d['market_flag']]['odds'] < $d['odds']
                                ) {
                                    $otherResult[$key][$d['odd_type']][$d['market_flag']]['odds'] = $d['odds'];
                                }

                                if (
                                    empty($otherResult[$key][$d['odd_type']][$d['market_flag']]['market_id']) ||
                                    (
                                        !empty($otherResult[$key][$d['odd_type']][$d['market_flag']]['market_id']) &&
                                        $d['provider_id'] == $primaryProviderId
                                    )
                                ) {
                                    $otherResult[$key][$d['odd_type']][$d['market_flag']]['market_id'] = $d['market_id'];
                                    $otherResult[$key][$d['odd_type']][$d['market_flag']]['mem_uid_from_provider'] = $d['provider_id'];

                                    if (SwooleHandler::exists('providerEventMarketsTable', $d['event_identifier'] . ":" . $d['odd_type'] . $d['market_flag'] . $d['points'])) {
                                        SwooleHandler::setColumnValue('providerEventMarketsTable', $d['event_identifier'] . ":" . $d['odd_type'] . $d['market_flag'] . $d['points'], 'mem_uid', $d['market_id']);
                                    }
                                }
                            }
                        }
                    }

                    krsort($otherResult, SORT_NUMERIC);
                    $data[$transformed->master_event_unique_id]['market_odds']['other'] = $otherResult;
                }

                if ($singleEvent) {
                    $data[$transformed->master_event_unique_id]['single_event_response'] = true;
                }

                if (empty($_SERVER['_PHPUNIT'])) {
                    $doesExist = false;
                    foreach ($topicTable as $topic) {
                        if ($topic['topic_name'] == 'uid-' . $transformed->master_event_unique_id &&
                            $topic['user_id'] == $userId) {
                            $doesExist = true;
                            break;
                        }
                    }
                    if (empty($doesExist)) {
                        $topicTable->set('userId:' . $userId . ':unique:' . uniqid(), [
                            'user_id'    => $userId,
                            'topic_name' => 'uid-' . $transformed->master_event_unique_id
                        ]);
                    }
                }
            }

            if ($type == 'selected') {
                $result[$transformed->game_schedule][$groupIndex] = $data;
            } else if ($type == 'watchlist') {
                $result[$groupIndex] = $data;
            } else {
                $result = $data;
            }
        }

        $newResult = [];

        if ($type == "selected") {
            foreach ($result as $gameSchedule => $row) {
                foreach ($row as $leagueName => $data) {
                    foreach ($data as $meuid => $event) {
                        if (($gameSchedule == $event['game_schedule']) && (strpos($leagueName, $event['league_name']) !== false)) {
                            $newResult[$gameSchedule][$leagueName][$meuid] = (object) $event;
                        }
                    }
                }
            }
        } else if ($type == "watchlist") {
            foreach ($result as $leagueName => $data) {
                foreach ($data as $meuid => $event) {
                    if (strpos($leagueName, $event['league_name']) !== false) {
                        $newResult[$leagueName][$meuid] = (object) $event;
                    }
                }
            }
        } else {
            return $result;
        }

        return $newResult;
    }
}

if (!function_exists('checkIfInSWTKey')) {
    function checkIfInSWTKey($swt, $toCheck)
    {
        $inSWT = false;
        foreach ($swt as $key => $row) {
            if (strpos($key, $toCheck) !== false) {
                $inSWT = true;
            } else {
                continue;
            }
        }
        return $inSWT;
    }
}

if (!function_exists('getFromSWT')) {
    function getFromSWT($swt, $toCheck)
    {
        $data = [];
        foreach ($swt as $key => $row) {
            if (strpos($key, $toCheck) !== false) {
                $data[] = $row;
            } else {
                continue;
            }
        }
        $data = json_decode(json_encode($data));
        return $data;
    }
}

if (!function_exists('appLog')) {
    function appLog($type, $payload)
    {
        if (env('APP_DEBUG')) {
            Log::{$type}($payload);
        }
    }
}

if (!function_exists('providerErrorMapping')) {
    function providerErrorMapping($string, bool $returnId = true)
    {
        $data = DB::select(DB::raw("SELECT * FROM provider_error_messages WHERE '" . pg_escape_string($string) . "' LIKE '%' || message || '%'"));
        if ($data) {
            if ($returnId) {
                return $data[0]->id;
            } else {
                return $data[0];
            }
        } else {
            return null;
        }
    }
}


if (!function_exists('orderStatus')) {
    function orderStatus($userId, $orderId, $status, $odds, $expiry, $createdAt, $retryType, $oddsHaveChanged, $error)
    {
        $swoole = app('swoole');

        if ($swoole->wsTable->exists('uid:' . $userId)) {
            $fd = $swoole->wsTable->get('uid:' . $userId);

            if ($swoole->isEstablished($fd['value'])) {
                $swoole->push($fd['value'], json_encode([
                    'getOrderStatus' => [
                        'order_id'          => $orderId,
                        'status'            => $status,
                        'odds'              => $odds,
                        'retry_type'        => $retryType,
                        'odds_have_changed' => $oddsHaveChanged,
                        'error'             => $error
                    ]
                ]));
            } else {
                $requestId = (string) Str::uuid();
                $payload         = [
                    'request_uid' => $requestId,
                    'request_ts'  => getMilliseconds(),
                    'sub_command' => 'order-status',
                    'command'     => 'socket'
                ];
                $payload['data'] = [
                    'user_id' => $userId,
                    'retry'   => 1,
                    'payload' => [
                        'getOrderStatus' => [
                            'order_id'          => $orderId,
                            'status'            => $status,
                            'odds'              => $odds,
                            'retry_type'        => $retryType,
                            'odds_have_changed' => $oddsHaveChanged,
                            'error'             => $error
                        ]
                    ]
                ];

                kafkaPush(env('KAFKA_SOCKET', 'SOCKET-DATA'), $payload, $requestId);
            }

            // $forBetBarRemoval = [
            //     'FAILED',
            //     'CANCELLED',
            // ];
            // if (in_array(strtoupper($status), $forBetBarRemoval)) {
            //     if (time() - strtotime($createdAt) > $expiry) {
            //         SwooleHandler::setValue('topicTable', 'userId:' . $userId . ':unique:' . uniqid(), [
            //             'user_id'    => $userId,
            //             'topic_name' => 'removal-bet-' . $orderId
            //         ]);
            //     }
            // }
        }
    }
}

if (!function_exists('kafkaPush')) {
    function kafkaPush($kafkaTopic, $message, $key)
    {
        $kafkaProducer   = app('KafkaProducer');
        $producerHandler = app('ProducerHandler');
        try {
            appLog('info', 'Sending to Kafka Topic: ' . $kafkaTopic);
            $producerHandler->setTopic($kafkaTopic)->send($message, $key);
            if (env('APP_ENV') != 'local') {
                for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
                    $result = $kafkaProducer->flush(10000);
                    if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                        break;
                    }
                }
            }
            appLog('info', 'Sent to Kafka Topic: ' . $kafkaTopic);
        } catch (Exception $e) {
            Log::critical('Sending Kafka Message Failed', [
                'error' => $e->getMessage(),
                'code'  => $e->getCode()
            ]);
        } finally {
            if (env('CONSUMER_PRODUCER_LOG', false)) {
                Log::channel('kafkaproducelog')->info(json_encode($message));
            }
        }
    }
}

if (!function_exists('monitorLog')) {
    function monitorLog(string $channel, string $level, $data)
    {
        Log::channel($channel)->{$level}(json_encode($data));
    }
}


if (!function_exists('retryCacheToRedis')) {
    function retryCacheToRedis($orderData)
    {
        Redis::rpush('ml-queue', json_encode($orderData));
    }
}
