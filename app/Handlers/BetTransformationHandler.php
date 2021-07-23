<?php

namespace App\Handlers;

use App\Facades\{WalletFacade, SwooleHandler};
use App\User;
use App\Models\{
    OddType,
    Order,
    ProviderAccount,
    OrderLogs,
    ProviderAccountOrder,
    UserWallet,
    Source,
    BlockedLine,
    EventMarket,
    SystemConfiguration,
    ProviderErrors,
    RetryType
};
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\{DB, Log};

class BetTransformationHandler
{
    protected $message;
    protected $offset;

    private $channel = 'bets';

    const STATUS_RECEIVED = 'received';
    const STATUS_PENDING  = 'pending';
    const STATUS_SUCCESS  = 'success';
    const STATUS_FAILED   = 'failed';

    public function init($message, $offset)
    {
        $this->message = $message;
        $this->offset  = $offset;

        return $this;
    }

    public function handle()
    {
        $toLogs = [
            "class"       => "BetTransformationHandler",
            "message"     => "Initiating...",
            "module"      => "HANDLER",
            "status_code" => 200,
        ];
        monitorLog('monitor_handlers', 'info', $toLogs);

        try {
            DB::beginTransaction();

            $swoole               = app('swoole');
            $topics               = $swoole->topicTable;
            $colMinusOne          = OddType::whereIn('type', ['1X2', 'HT 1X2', 'OE'])->pluck('id')->toArray();
            $requestUIDArray      = explode('-', $this->message->request_uid);
            $messageOrderId       = end($requestUIDArray);
            $orderData            = Order::find($messageOrderId);
            $providerAccount      = ProviderAccount::find($orderData->provider_account_id);
            $eventId              = EventMarket::withTrashed()->where('bet_identifier', $orderData->market_id)->first()->event_id;
            $blockedLineReasons   = explode('|', env('BLOCKED_LINE_REASONS', ''));
            $hasBlockedLineReason = false;

            foreach($blockedLineReasons as $reason) {
                if(stripos($this->message->data->reason, $reason) !== false) {
                    $hasBlockedLineReason = true;
                }
            }

            if ($this->message->data->status == self::STATUS_RECEIVED) {
                if (time() - strtotime($orderData->created_at) > 60) {
                    $orderData->status     = 'FAILED';
                    $orderData->reason     = 'Expired';
                    $orderData->updated_at = Carbon::now();
                    $orderData->save();

                    $orderSWTKey = 'orderId:' . $messageOrderId;
                    SwooleHandler::setColumnValue('ordersTable', $orderSWTKey, 'status', 'FAILED');

                    if (!empty($providerAccount->id)) {
                        if (!empty($hasBlockedLineReason)) {
                            BlockedLine::updateOrCreate([
                                'event_id'    => $eventId,
                                'odd_type_id' => $orderData->odd_type_id,
                                'points'      => $orderData->odd_label,
                                'line'        => $providerAccount->line
                            ]);
                        }
                    }
                }
            } else {
                if ($orderData) {
                    $orderId         = $orderData->id;
                    $status          = $this->message->data->status != self::STATUS_PENDING ? strtoupper($this->message->data->status) : strtoupper(self::STATUS_SUCCESS);
                    $errorMessageId  = providerErrorMapping($this->message->data->reason);
                    $retryType       = null;
                    $oddsHaveChanged = false;
                    $error           = null;

                    $order = Order::updateOrCreate([
                        'id' => $messageOrderId
                    ], [
                        'bet_id'                    => $this->message->data->bet_id,
                        'reason'                    => $this->message->data->reason,
                        'status'                    => $status,
                        'odds'                      => empty($this->message->data->odds) ? (float) 0.00 : $this->message->data->odds,
                        'provider_error_message_id' => $errorMessageId
                    ]);

                    if ($status != strtoupper(self::STATUS_FAILED)) {
                        $providerAccount->update([
                            'updated_at' => Carbon::now()
                        ]);

                        $betSelectionArray         = explode("\n", $order->bet_selection);
                        $betSelectionTeamOddsArray = explode('@ ', $betSelectionArray[1]);
                        $updatedOrderOdds          = $betSelectionTeamOddsArray[0] . '@ ' . number_format($order->odds, 2);
                        $order->bet_selection      = implode("\n", [
                            $betSelectionArray[0],
                            $updatedOrderOdds,
                            $betSelectionArray[2]
                        ]);

                        $order->to_win = !in_array($order->odd_type_id, $colMinusOne) ? $order->stake * $this->message->data->odds : $order->stake * ($this->message->data->odds - 1);
                        $order->save();

                        $orderLogData         = OrderLogs::where('order_id', $orderData->id)->orderBy('id', 'desc')->first();
                        $providerAccountOrder = ProviderAccountOrder::where('order_log_id', $orderLogData->id)->orderBy('id', 'desc')->first();

                        $actualStake    = $providerAccountOrder->actual_stake;
                        $exchangeRate   = $providerAccountOrder->exchange_rate;
                        $exchangeRateId = $providerAccountOrder->exchange_rate_id;

                        $orderLogs = OrderLogs::create([
                            'provider_id'   => $order->provider_id,
                            'sport_id'      => $order->sport_id,
                            'bet_id'        => $this->message->data->bet_id,
                            'bet_selection' => $order->bet_selection,
                            'status'        => $status,
                            'user_id'       => $order->user_id,
                            'reason'        => $this->message->data->reason,
                            'profit_loss'   => $order->profit_loss,
                            'order_id'      => $order->id,
                            'settled_date'  => null,
                            'provider_account_id' => $order->provider_account_id
                        ]);

                        ProviderAccountOrder::create([
                            'order_log_id'       => $orderLogs->id,
                            'exchange_rate_id'   => $exchangeRateId,
                            'actual_stake'       => $actualStake,
                            'actual_to_win'      => !in_array($order->odd_type_id, $colMinusOne) ? $actualStake * $order->odds : $actualStake * ($order->odds - 1),
                            'actual_profit_loss' => 0.00,
                            'exchange_rate'      => $exchangeRate,
                        ]);

                        if ($status == strtoupper(self::STATUS_SUCCESS)) {
                            SwooleHandler::remove('pendingOrdersWithinExpiryTable', 'orderId:' . $orderId);
                        }

                        SwooleHandler::setColumnValue('ordersTable', 'orderId:' . $messageOrderId, 'bet_id', $this->message->data->bet_id);
                        SwooleHandler::setColumnValue('ordersTable', 'orderId:' . $messageOrderId, 'status', $status);

                        orderStatus(
                            $orderData->user_id,
                            $orderId,
                            $status,
                            $this->message->data->odds,
                            $orderData->orderExpiry,
                            $orderData->created_at,
                            $retryType,
                            $oddsHaveChanged,
                            $error
                        );
                    } else {
                        $retryExpiry = SystemConfiguration::getSystemConfigurationValue('RETRY_EXPIRY')->value;

                        $mappedProviderError = false;
                        if(!empty($errorMessageId)) {
                            $providerErrorMessage = ProviderErrors::getProviderErrorMessage($errorMessageId);
                            if($providerErrorMessage->exists()) {
                                $providerError   = $providerErrorMessage->first();
                                $retryType       = $providerError->retry_type;
                                $oddsHaveChanged = $providerError->odds_have_changed;
                                $error           = $providerError->error;

                                $mappedProviderError = true;
                            }
                        } else {
                            $error = $this->message->data->reason;
                        }

                        $providerErrorMessage = providerErrorMapping($this->message->data->reason, false);
                        $orderLogData         = OrderLogs::where('order_id', $orderData->id)->orderBy('id', 'desc')->first();
                        $providerAccountOrder = ProviderAccountOrder::where('order_log_id', $orderLogData->id)->orderBy('id', 'desc')->first();
                        $actualStake          = $providerAccountOrder->actual_stake;
                        $exchangeRate         = $providerAccountOrder->exchange_rate;
                        $exchangeRateId       = $providerAccountOrder->exchange_rate_id;

                        $orderLogs = OrderLogs::create([
                            'provider_id'         => $order->provider_id,
                            'sport_id'            => $order->sport_id,
                            'bet_id'              => $this->message->data->bet_id,
                            'bet_selection'       => $order->bet_selection,
                            'status'              => $status,
                            'user_id'             => $order->user_id,
                            'reason'              => $this->message->data->reason,
                            'profit_loss'         => $order->profit_loss,
                            'order_id'            => $order->id,
                            'settled_date'        => null,
                            'provider_account_id' => $orderData->provider_account_id,
                        ]);

                        ProviderAccountOrder::create([
                            'order_log_id'       => $orderLogs->id,
                            'exchange_rate_id'   => $exchangeRateId,
                            'actual_stake'       => $actualStake,
                            'actual_to_win'      => !in_array($order->odd_type_id, $colMinusOne) ? $actualStake * $order->odds : $actualStake * ($order->odds - 1),
                            'actual_profit_loss' => 0.00,
                            'exchange_rate'      => $exchangeRate,
                        ]);

                        if (!empty($providerAccount->id)) {
                            if (!empty($hasBlockedLineReason)) {
                                BlockedLine::updateOrCreate([
                                    'event_id'    => $eventId,
                                    'odd_type_id' => $orderData->odd_type_id,
                                    'points'      => $orderData->odd_label,
                                    'line'        => $providerAccount->line
                                ]);
                            }
                        }

                        $maxRetryCount = SystemConfiguration::getSystemConfigurationValue('RETRY_COUNT');
                        $retryExpiry   = SystemConfiguration::getSystemConfigurationValue('RETRY_EXPIRY');
                        $now           = Carbon::now();
                        $betData       = Order::retryBetData($orderData->id)->toArray();

                        if (
                            (
                                empty($mappedProviderError) ||
                                 (
                                     $providerErrorMessage->retry_type_id &&
                                     RetryType::getTypeById($providerErrorMessage->retry_type_id) != "manual-same-account"
                                 )
                             ) &&
                            $orderData->retry_count < $maxRetryCount->value &&
                            $now->diffInSeconds($orderData->created_at) <= $retryExpiry['value']
                        ) {
                            if (empty($mappedProviderError)) {
                                $betData['retry_type_id'] = RetryType::getIdByType("auto-new-account");
                                var_dump('retry new account not mapped');
                            } else {
                                $betData['retry_type_id'] = $providerErrorMessage->retry_type_id;
                            }

                            $orderData->retry_count += 1;
                            $orderData->save();

                            $toLogs = [
                                "class"   => "BetTransformationHandler",
                                "message" => json_encode($betData),
                                "module"  => "HANDLER_INFO"
                            ];
                            monitorLog('monitor_handlers', 'info', $toLogs);

                            retryCacheToRedis($betData);
                        } else {
                            $orderData->status = "FAILED";
                            $orderData->reason = $this->message->data->reason;

                            if ($providerErrorMessage) {
                                $orderData->provider_error_message_id = $providerErrorMessage->id;
                            }

                            $orderData->updated_at = Carbon::now();
                            $orderData->save();

                            $source       = Source::where('source_name', 'LIKE', 'RETURN_STAKE')->first();
                            $walletToken  = SwooleHandler::getValue('walletClientsTable', 'ml-users')['token'];
                            $user         = User::find($order->user_id);
                            $currencyCode = $user->currency()->first()->code;
                            $reason       = "[RETURN_STAKE][BET FAILED/CANCELLED] - transaction for order id " . $order->id;
                            $userBalance  = WalletFacade::addBalance($walletToken, $user->uuid, trim(strtoupper($currencyCode)), $order->stake, $reason);

                            orderStatus(
                                $orderData->user_id,
                                $orderId,
                                $status,
                                $this->message->data->odds,
                                $orderData->orderExpiry,
                                $orderData->created_at,
                                $retryType,
                                $oddsHaveChanged,
                                $error
                            );
                        }
                    }
                }
            }

            DB::commit();

            $toLogs = [
                "class"       => "BetTransformationHandler",
                "message"     => "Processed (offset: " . $this->offset . ", data: " . json_encode((array) $this->message) . ")",
                "module"      => "HANDLER",
                "status_code" => 200,
            ];
            monitorLog('monitor_handlers', 'info', $toLogs);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "BetTransformationHandler",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage() . " | " . $e->getFile(),
                "module"      => "HANDLER_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_handlers', 'error', $toLogs);

            DB::rollBack();
        }
    }
}
