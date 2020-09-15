<?php

namespace App\Handlers;

use App\Facades\SwooleHandler;

use App\Models\{OddType, Order, ProviderAccount, OrderLogs, ProviderAccountOrder, UserWallet, Source};

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\{DB, Log};

class BetTransformationHandler
{
    protected $message;

    const STATUS_RECEIVED = 'received';
    const STATUS_PENDING  = 'pending';
    const STATUS_SUCCESS  = 'success';
    const STATUS_FAILED   = 'failed';

    public function init($message)
    {
        Log::info('BetTransformationHandler : CONSTRUCT');

        $this->message = $message;
        return $this;
    }

    public function handle()
    {
        Log::info('BetTransformationHandler : HANDLE');

        try {
            DB::beginTransaction();

            $swoole = app('swoole');
            $topics = $swoole->topicTable;
            $col1x2 = OddType::whereIn('type', ['1X2', 'HT 1X2'])->pluck('id')->toArray();

            $requestUIDArray = explode('-', $this->message->request_uid);
            $messageOrderId  = end($requestUIDArray);

            if ($this->message->data->status == self::STATUS_RECEIVED) {
                $order = Order::find($messageOrderId);

                if (time() - strtotime($order->created_at) > 60) {
                    $order->status = 'FAILED';
                    $order->reason = 'Expired';
                    $order->updated_at = Carbon::now();
                    $order->save();

                    $orderSWTKey = 'orderId:' . $messageOrderId;
                    SwooleHandler::setColumnValue('ordersTable', $orderSWTKey, 'status', 'FAILED');
                }
            } else {
                SwooleHandler::remove('orderRetriesTable', 'orderId:' . $messageOrderId);
                foreach ($topics as $key => $row) {
                    if (strpos($row['topic_name'], 'order-') === 0) {
                        $orderId   = substr($row['topic_name'], strlen('order-'));
                        $orderData = Order::where('id', $messageOrderId);

                        if ($orderData->count() && $orderId == $messageOrderId) {

                            $status = $this->message->data->status != self::STATUS_PENDING ? strtoupper($this->message->data->status) : strtoupper(self::STATUS_SUCCESS);
                            $order  = Order::updateOrCreate([
                                'id' => $messageOrderId
                            ], [
                                'bet_id' => $this->message->data->bet_id,
                                'reason' => $this->message->data->reason,
                                'status' => $status,
                                'odds'   => $this->message->data->odds
                            ]);

                            $orderData = Order::find($messageOrderId);
                            if ($status != strtoupper(self::STATUS_FAILED)) {
                                ProviderAccount::find($order->provider_account_id)->update([
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

                                $order->to_win = !in_array($order->odd_type_id, $col1x2) ? $order->stake * $this->message->data->odds : $order->stake * ($this->message->data->odds - 1);
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
                                ]);

                                ProviderAccountOrder::create([
                                    'order_log_id'       => $orderLogs->id,
                                    'exchange_rate_id'   => $exchangeRateId,
                                    'actual_stake'       => $actualStake,
                                    'actual_to_win'      => !in_array($order->odd_type_id, $col1x2) ? $actualStake * $order->odds : $actualStake * ($order->odds - 1),
                                    'actual_profit_loss' => 0.00,
                                    'exchange_rate'      => $exchangeRate,
                                ]);
                            } else {
                                $userWallet = UserWallet::where('user_id', $order->user_id)->first();
                                $source     = Source::where('source_name', 'LIKE', 'RETURN_STAKE')->first();
                                $orderLogs  = OrderLogs::create([
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
                                ]);

                                if ($order->status == strtoupper(self::STATUS_SUCCESS)) {
                                    continue;
                                }

                                UserWallet::makeTransaction(
                                    $order->user_id,
                                    $order->stake,
                                    $userWallet->currency_id,
                                    $source->id,
                                    'Credit'
                                );
                            }

                            if ($status == strtoupper(self::STATUS_SUCCESS)) {
                                SwooleHandler::remove('pendingOrdersWithinExpiryTable', 'orderId:' . $orderId);
                            }
                            orderStatus(
                                $row['user_id'],
                                $orderId,
                                $status,
                                $this->message->data->odds,
                                $orderData->orderExpiry,
                                $orderData->created_at
                            );

                            $topics->set('unique:' . uniqid(), [
                                'user_id'    => $row['user_id'],
                                'topic_name' => 'open-order-' . $this->message->data->bet_id
                            ]);

                            SwooleHandler::setColumnValue('ordersTable', 'orderId:' . $messageOrderId, 'bet_id', $this->message->data->bet_id);
                            SwooleHandler::setColumnValue('ordersTable', 'orderId:' . $messageOrderId, 'status', $status);
                        }
                    }
                }
            }

            DB::commit();
        } catch (Exception $e) {
            Log::error(json_encode([
                'BetTransformationHandler' => [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                ]
            ]));

            DB::rollBack();
        }
    }
}
