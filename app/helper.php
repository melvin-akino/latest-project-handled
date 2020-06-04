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

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\{Blade, File, Route, Cookie};
use Illuminate\Http\Request;
use App\Models\{
    Sport,
    UserConfiguration,
    UserWallet,
    Source,
    Order,
    UserProviderConfiguration
};
use App\Models\CRM\{
    OrderTransaction,
    WalletLedger
};

use RdKafka\Conf as KafkaConf;

/* Datatable for CRM admin */

function dataTable(Request $request, $query, $cols = null)
{
    $order  = collect($request->input('order')[0]);
    $col    = collect($request->input('columns')[$order->get('column')])->get('data');
    $dir    = $order->get('dir');
    $q      = trim($request->input('search')['value']);
    $len    = $request->input('length');
    $page   = ($request->input('start') / $len) + 1;

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
        "data" => $pagin->items()
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
 * @param   string   $cookieName     Illuminate\Support\Facades\Cookie;
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
 * @param   int        $userId
 * @param   string     $type
 * @param   array|null $data
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
 * @param   int    $userId
 * @param   string $type
 * @return  $data
 *
 * @author  Kevin Uy
 */
if (!function_exists('getUserDefault')) {
    function getUserDefault(int $userId, string $type)
    {
        $data = [];
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
                        $sport = $defaultSport->first()->id;
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
                        'status'        => true,
                        'default_sort'  => $sort,
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
 * @param  int     $userId          Authenticated User's ID
 * @param  string  $transactionType 'source_name' from 'source' Database Table
 * @param  float   $amount          Amount from Transaction (MUST already be converted to Application's Base Currency [CNY])
 * @param  float   $orderLogsId     Order Logs ID
 */
if (!function_exists('userWalletTransaction')) {
    function userWalletTransaction($userId, $transactionType, $amount, $orderLogsId)
    {
        switch ($transactionType) {
            case 'PLACE_BET':
                $userWallet  = UserWallet::where('user_id', $userId);
                $walletId    = $userWallet->first()->id;
                $userBalance = $userWallet->first()->balance;
                $currencyId  = $userWallet->first()->currency_id;
                $sourceId    = Source::where('source_name', $transactionType)->first()->id;
                $newBalance  = $userBalance - $amount;

                $userWallet->update(
                    [ 'balance' => $newBalance ]
                );

                $ledgerId = WalletLedger::create(
                    [
                        'wallet_id' => $walletId,
                        'source_id' => $sourceId,
                        'credit'    => 0,
                        'debit'     => $amount,
                        'balance'   => $newBalance,
                    ]
                )->id;

                OrderTransaction::create(
                    [
                        'wallet_ledger_id'    => $ledgerId,
                        'provider_account_id' => 0,
                        'order_logs_id'       => $orderLogsId,
                        'user_id'             => $userId,
                        'source_id'           => $sourceId,
                        'currency_id'         => $currencyId,
                        'reason'              => "Placed Bet",
                        'amount'              => $amount,
                    ]
                );
            break;

            /** TO DO: Add more cases for every User Transaction catered by the application */
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

if (!function_exists('getMostPriorityProvider')) {
    function getMostPriorityProvider(int $userId)
    {
        $userProvider = UserProviderConfiguration::where('user_id', $userId)
            ->join('providers', 'provider_id', 'providers.id');
        if ($userProvider->exists()) {
            $userProvider = $userProvider->where('active', true)->orderBy('priority', 'ASC');
            $userProvider = $userProvider->first()->provider_id;
        } else {
            $userProvider = Provider::where('is_enabled', true)->orderBy('priority', 'ASC');
            $userProvider = $userProvider->first()->id;
        }
        return $userProvider;
    }
}
