<?php

namespace App\Processes;

use App\Facades\SwooleHandler;
use App\Handlers\ProducerHandler;
use App\Jobs\{KafkaPush, WSForBetBarRemoval};
use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Str;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;
use Carbon\Carbon;
use PrometheusMatric;
use App\Models\{CRM\ProviderAccount,
    CRM\WalletLedger,
    Events,
    ExchangeRate,
    Order,
    OrderLogs,
    ProviderAccountOrder,
    Source,
    UserWallet
};
use App\User;

class BetProduce implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;
    private static $producerHandler;

    const INTERNAL_CNY_USER         = 1000;
    const INTERNAL_USD_USER         = 1001;
    const DEFAULT_PROVIDER_CURRENCY = 1;
    const SOURCE_BET_TIMEDOUT       = 'BET_TIMED_OUT';
    const SOURCE_PLACE_BET          = 'PLACE_BET';

    public static function callback(Server $swoole, Process $process)
    {
        try {
            $kafkaProducer         = app('KafkaProducer');
            self::$producerHandler = new ProducerHandler($kafkaProducer);

            if ($swoole->data2SwtTable->exist('data2Swt')) {
                $orderRetriesTable  = $swoole->orderRetriesTable;
                $providersTable     = $swoole->providersTable;
                $topicsTable        = $swoole->topicTable;
                $initialTime        = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));

                $timedOutSourceId  = Source::where('source_name', 'LIKE', self::SOURCE_BET_TIMEDOUT)->first();
                $placedBetSourceId = Source::where('source_name', 'LIKE', self::SOURCE_PLACE_BET)->first();
                while (!self::$quit) {
                    $newTime = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));

                    if ($newTime->diffInSeconds(Carbon::parse($initialTime)) >= 1) {
                        foreach ($orderRetriesTable as $key => $row) {
                            if ($newTime->diffInSeconds(Carbon::parse($row['time'])) >= 30) {
                                $orderId = substr($key, strlen('orderId:'));

                                $orderRetriesTable->del($key);

                                $order = Order::find($orderId);

                                if (!empty($order->bet_id)) {
                                    continue;
                                }

                                if (in_array($order->user_id, [self::INTERNAL_CNY_USER, self::INTERNAL_USD_USER])) {
                                    Log::info('Bet Produce - Internal Users don\'t need to retry');
                                    continue;
                                }
                                $duplicateOrder = $order->replicate();

                                $orderLog          = OrderLogs::where('order_id', $orderId)->first();
                                $duplicateOrderLog = $orderLog->replicate();


                                $providerCurrency = self::DEFAULT_PROVIDER_CURRENCY;
                                $providerAlias    = '';
                                $doesExist        = false;
                                foreach ($providersTable as $k => $r) {
                                    if ($r['id'] == $order->provider_id) {
                                        $providerAlias    = strtolower($r['alias']);
                                        $providerCurrency = $providersTable->get('providerAlias:' . $providerAlias)['currency_id'];
                                        $doesExist        = true;
                                        break;
                                    }
                                }

                                if (!$doesExist) {
                                    Log::info('Bet Produce - Provider doesn\'t exist');
                                    continue;
                                }

                                $exchangeRate = ExchangeRate::where('from_currency_id', $providerCurrency)->where('to_currency_id', self::DEFAULT_PROVIDER_CURRENCY)->first();

                                $orderUser = User::find($order->user_id);
                                if ($orderUser) {
                                    switch ($orderUser->currency_id) {
                                        case 1:
                                            $order->user_id = $orderLog->user_id = self::INTERNAL_CNY_USER;
                                            break;
                                        case 2:
                                        default:
                                            $order->user_id = $orderLog->user_id = self::INTERNAL_USD_USER;
                                            break;
                                    }

                                    $statusFailed       = 'FAILED';
                                    $statusFailedReason = 'Timed Out';

                                    $order->updated_at = Carbon::now();
                                    $order->save();

                                    $orderLog->save();

                                    $orderLogs = OrderLogs::create([
                                        'provider_id'   => $orderLog->provider_id,
                                        'sport_id'      => $orderLog->sport_id,
                                        'bet_id'        => $orderLog->bet_id,
                                        'bet_selection' => $orderLog->bet_selection,
                                        'status'        => $statusFailed,
                                        'user_id'       => $orderLog->user_id,
                                        'reason'        => $statusFailedReason,
                                        'profit_loss'   => 0.00,
                                        'order_id'      => $orderId,
                                        'settled_date'  => $orderLog->settled_date
                                    ]);

                                    $orderLogsId = $orderLogs->id;
                                    $stake       = $order->stake;
                                    $balance     = $stake * $exchangeRate->exchange_rate;

                                    $providerAccountOrder          = ProviderAccountOrder::where('order_log_id', $orderLog->id)->first();
                                    $duplicateProviderAccountOrder = $providerAccountOrder->replicate();

                                    $duplicateProviderAccountOrder->order_log_id = $orderLogsId;
                                    $duplicateProviderAccountOrder->save();

                                    $duplicateUserProviderAccountOrder = $duplicateProviderAccountOrder->replicate();

                                    $internalUserWallet = UserWallet::where('user_id', $order->user_id)->first();
                                    $userWallet         = UserWallet::where('user_id', $orderUser->id)->first();

                                    $newBalance                     = $internalUserWallet->balance - $balance;
                                    $internalUserWallet->balance    = $newBalance;
                                    $internalUserWallet->updated_at = Carbon::now();
                                    $internalUserWallet->save();

                                    WalletLedger::create([
                                        'wallet_id' => $internalUserWallet->id,
                                        'source_id' => $placedBetSourceId->id,
                                        'debit'     => $stake,
                                        'credit'    => 0,
                                        'balance'   => $newBalance
                                    ]);


                                    //created new order record and order logs record
                                    $duplicateOrder->created_at = $order->created_at;
                                    $duplicateOrder->save();
                                    $duplicateOrderLog->order_id = $duplicateOrder->id;
                                    $duplicateOrderLog->user_id  = $duplicateOrder->user_id;
                                    $duplicateOrderLog->save();
                                    $duplicateUserProviderAccountOrder->order_log_id = $duplicateOrderLog->id;
                                    $duplicateUserProviderAccountOrder->save();

                                    $newBalance             = $userWallet->balance + $balance;
                                    $userWallet->balance    = $newBalance;
                                    $userWallet->updated_at = Carbon::now();
                                    $userWallet->save();

                                    //Credit to User
                                    WalletLedger::create([
                                        'wallet_id' => $userWallet->id,
                                        'source_id' => $timedOutSourceId->id,
                                        'debit'     => 0,
                                        'credit'    => $stake,
                                        'balance'   => $newBalance
                                    ]);

                                    $newBalance             = $userWallet->balance - $balance;
                                    $userWallet->balance    = $newBalance;
                                    $userWallet->updated_at = Carbon::now();
                                    $userWallet->save();

                                    //Debit to User
                                    WalletLedger::create([
                                        'wallet_id' => $userWallet->id,
                                        'source_id' => $placedBetSourceId->id,
                                        'debit'     => $stake,
                                        'credit'    => 0,
                                        'balance'   => $newBalance
                                    ]);

                                    $event           = Events::getEventByMarketId($duplicateOrder->market_id);
                                    $providerAccount = ProviderAccount::find($duplicateOrder->provider_account_id);

                                    $requestId       = Str::uuid() . "-" . $duplicateOrder->id;
                                    $requestTs       = getMilliseconds();
                                    $payload         = [
                                        'request_uid' => $requestId,
                                        'request_ts'  => $requestTs,
                                        'sub_command' => 'place',
                                        'command'     => 'bet'
                                    ];
                                    $payload['data'] = [
                                        'provider'  => $providerAlias,
                                        'sport'     => $duplicateOrder->sport_id,
                                        'stake'     => $duplicateProviderAccountOrder->actual_stake,
                                        'odds'      => $duplicateOrder->odds,
                                        'market_id' => $duplicateOrder->market_id,
                                        'event_id'  => $event->event_identifier,
                                        'score'     => $duplicateOrder->score_on_bet,
                                        'username'  => $providerAccount->username,
                                    ];

                                    $orderSWTKey = 'orderId:' . $duplicateOrder->id;
                                    SwooleHandler::setColumnValue('ordersTable', $orderSWTKey, 'username', $providerAccount->username);
                                    SwooleHandler::setColumnValue('ordersTable', $orderSWTKey, 'orderExpiry', $duplicateOrder->order_expiry);
                                    SwooleHandler::setColumnValue('ordersTable', $orderSWTKey, 'created_at', $duplicateOrder->created_at);
                                    SwooleHandler::setColumnValue('ordersTable', $orderSWTKey, 'status', 'PENDING');

                                    $doesExist = false;
                                    foreach ($topicsTable AS $tKey => $tRow) {
                                        if ($tRow['topic_name'] == 'order-' . $orderId) {
                                            SwooleHandler::remove('topicTable', $tKey);
                                            $doesExist = true;
                                            break;
                                        }
                                    }
                                    if ($doesExist) {
                                        //set topic to internal user
                                        $topicsId = implode(':', [
                                            "userId:" . $order->user_id,
                                            "unique:" . $orderId,
                                        ]);


                                        if (!SwooleHandler::exists('topicTable', $topicsId)) {
                                            SwooleHandler::setValue('topicTable', $topicsId, [
                                                'user_id'    => $order->user_id,
                                                'topic_name' => "order-" . $orderId
                                            ]);
                                        }

                                        //new topic for user
                                        $topicsId = implode(':', [
                                            "userId:" . $orderUser->id,
                                            "unique:" . $duplicateOrder->id,
                                        ]);


                                        if (!SwooleHandler::exists('topicTable', $topicsId)) {
                                            SwooleHandler::setValue('topicTable', $topicsId, [
                                                'user_id'    => $orderUser->id,
                                                'topic_name' => "order-" . $duplicateOrder->id
                                            ]);
                                        }

                                        $fd = SwooleHandler::getValue('wsTable', 'uid:' . $orderUser->id);
                                        WSForBetBarRemoval::dispatch($fd['value'], $orderId);
                                        SwooleHandler::remove('pendingOrdersWithinExpiryTable', 'orderId:' . $orderId);
                                        SwooleHandler::setValue('pendingOrdersWithinExpiryTable', 'orderId:' . $duplicateOrder->id, [
                                            'user_id'      => $orderUser->id,
                                            'id'           => $duplicateOrder->id,
                                            'created_at'   => $duplicateOrder->created_at,
                                            'order_expiry' => $duplicateOrder->order_expiry
                                        ]);
                                    }

                                    KafkaPush::dispatch(
                                        $providerAlias . env('KAFKA_SCRAPE_ORDER_REQUEST_POSTFIX', '_bet_req'),
                                        $payload,
                                        $requestId
                                    );
                                }
                            }
                        }
                        $initialTime = $newTime;
                    }
                    usleep(1000000);
                }
            }
        } catch (Exception $e) {
            Log::error(json_encode([
                'BetProduce' => [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                ]
            ]));
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
