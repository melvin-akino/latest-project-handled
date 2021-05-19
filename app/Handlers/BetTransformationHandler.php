<?php

namespace App\Handlers;

use App\Facades\{WalletFacade, SwooleHandler};
use App\User;
use App\Models\{OddType,
    Order,
    ProviderAccount,
    OrderLogs,
    ProviderAccountOrder,
    UserWallet,
    Source,
    UserBet,
    ProviderBet,
    ProviderBetLog,
    ProviderBetTransaction,
    Provider
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

            $swoole          = app('swoole');
            $topics          = $swoole->topicTable;
            $colMinusOne     = OddType::whereIn('type', ['1X2', 'HT 1X2', 'OE'])->pluck('id')->toArray();
            $requestUIDArray = explode('-', $this->message->request_uid);
            $messageOrderId  = end($requestUIDArray);
            $orderData       = ProviderBet::find($messageOrderId);
            $userBet         = UserBet::find($orderData->user_bet_id);

            if ($this->message->data->status == self::STATUS_RECEIVED) {
                SwooleHandler::decCtr('minMaxRequestsTable', $userBet->mem_uid . ":" . strtolower($this->message->data->provider));
            } else {
                if ($orderData) {
                    $orderId        = $orderData->id;
                    $status         = $this->message->data->status != self::STATUS_PENDING ? strtoupper($this->message->data->status) : strtoupper(self::STATUS_SUCCESS);
                    $errorMessageId = providerErrorMapping($this->message->data->reason);

                    $order = ProviderBet::updateOrCreate([
                        'id' => $messageOrderId
                    ], [
                        'bet_id'                    => $this->message->data->bet_id,
                        'reason'                    => $this->message->data->reason,
                        'status'                    => $status,
                        'odds'                      => empty($this->message->data->odds) ? (float) 0.00 : $this->message->data->odds,
                        'provider_error_message_id' => $errorMessageId
                    ]);

                    if ($status != strtoupper(self::STATUS_FAILED)) {
                        ProviderAccount::find($order->provider_account_id)->update([
                            'updated_at' => Carbon::now()
                        ]);

                        $order->to_win = !in_array($order->odd_type_id, $colMinusOne) ? $order->stake * $this->message->data->odds : $order->stake * ($this->message->data->odds - 1);
                        $order->save();

                        $orderLogData   = ProviderBetLog::where('provider_bet_id', $orderData->id)->orderBy('id', 'desc')->first();
                        $transaction    = ProviderBetTransaction::where('provider_bet_id', $orderData->id)->orderBy('id', 'desc')->first();
                        $actualStake    = $transaction->actual_stake;
                        $exchangeRate   = $transaction->exchange_rate;
                        $exchangeRateId = $transaction->exchange_rate_id;

                        $orderLogs = ProviderBetLog::create([
                            'provider_bet_id' => $orderData->id,
                            'status'          => $status,
                        ]);

                        ProviderBetTransaction::create([
                            'provider_bet_id'    => $orderData->id,
                            'order_log_id'       => $orderLogs->id,
                            'exchange_rate_id'   => $exchangeRateId,
                            'actual_stake'       => $actualStake,
                            'actual_to_win'      => !in_array($order->odd_type_id, $colMinusOne) ? $actualStake * $order->odds : $actualStake * ($order->odds - 1),
                            'actual_profit_loss' => 0.00,
                            'exchange_rate'      => $exchangeRate,
                            'punter_percentage'  => Provider::find($order->provider_id)->punter_percentage,
                        ]);
                    } else {
                        $source       = Source::where('source_name', 'LIKE', 'RETURN_STAKE')->first();
                        $walletToken  = SwooleHandler::getValue('walletClientsTable', 'ml-users')['token'];
                        $user         = User::find($userBet->user_id);
                        $currencyCode = $user->currency()->first()->code;
                        $reason       = "[RETURN_STAKE][BET FAILED/CANCELLED] - transaction for order id " . $order->id;
                        $userBalance  = WalletFacade::addBalance($walletToken, $user->uuid, trim(strtoupper($currencyCode)), $order->stake, $reason);

                        $orderLogs = ProviderBetLog::create([
                            'provider_bet_id' => $orderData->id,
                            'status'          => $status,
                        ]);

                        if ($order->status == strtoupper(self::STATUS_SUCCESS)) {
                            return;
                        }
                    }

                    if ($status == strtoupper(self::STATUS_SUCCESS)) {
                        SwooleHandler::remove('pendingOrdersWithinExpiryTable', 'orderId:' . $orderId);
                    }

                    orderStatus(
                        $userBet->user_id,
                        $orderData->user_bet_id,
                        $orderData->orderExpiry,
                        $orderData->created_at
                    );

                    $topics->set('unique:' . uniqid(), [
                        'user_id'    => $userBet->user_id,
                        'topic_name' => 'open-order-' . $this->message->data->bet_id
                    ]);

                    SwooleHandler::setColumnValue('ordersTable', 'orderId:' . $messageOrderId, 'bet_id', $this->message->data->bet_id);
                    SwooleHandler::setColumnValue('ordersTable', 'orderId:' . $messageOrderId, 'status', $status);
                }
            }

            DB::commit();

            $toLogs = [
                "class"       => "BetTransformationHandler",
                "message"     => "Processed (open-order-" . $this->message->data->bet_id . ")",
                "module"      => "HANDLER",
                "status_code" => 200,
                "payload"     => $this->message,
            ];
            monitorLog('monitor_handlers', 'info', $toLogs);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "BetTransformationHandler",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "HANDLER_ERROR",
                "status_code" => $e->getCode(),
                "payload"     => $this->message,
            ];
            monitorLog('monitor_handlers', 'error', $toLogs);

            DB::rollBack();
        }
    }
}
