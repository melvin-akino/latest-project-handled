<?php

namespace App\Jobs;

use App\Jobs\WSOrderStatus;

use App\Models\{
    Order,
    ProviderAccount,
    OrderLogs,
    ProviderAccountOrder
};

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\{DB, Log};

class TransformKafkaMessageBet implements ShouldQueue
{
    use Dispatchable;

    protected $message;

    public function __construct($message)
    {
        \Log::info('TransformKafkaMessageBet : CONSTRUCT');

        $this->message = $message;
    }

    public function handle()
    {
        \Log::info('TransformKafkaMessageBet : HANDLE');

        try {
            DB::beginTransaction();

            $swoole        = app('swoole');
            $topics        = $swoole->topicTable;
            $ordersTable   = $swoole->ordersTable;
            $payloadsTable = $swoole->payloadsTable;

            foreach ($topics AS $key => $row) {
                if (strpos($row['topic_name'], 'order-') === 0) {
                    $orderId         = substr($row['topic_name'], strlen('order-'));
                    $requestUIDArray = explode('-', $this->message->request_uid);
                    $messageOrderId  = end($requestUIDArray);
                    $orderData       = Order::where('id', $messageOrderId);

                    if ($orderData->count()) {
                        $status = strtoupper($this->message->data->status);

                        if ($status == 'FAILED') {
                            continue;
                        }

                        $order = Order::updateOrCreate([
                            'id' => $messageOrderId
                        ], [
                            'bet_id' => $this->message->data->bet_id,
                            'reason' => $this->message->data->reason,
                            'status' => $status,
                            'odds'   => $this->message->data->odds
                        ]);

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

                        $order->to_win = $order->stake * $this->message->data->odds;
                        $order->save();

                        $payloadsSwtId = implode(':', [
                            "place-bet-" . $messageOrderId,
                            "uId:" . $row['user_id'],
                            "mId:" . $order->market_id
                        ]);

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

                        $payload        = json_decode($payloadsTable->get($payloadsSwtId)['payload']);
                        $actualStake    = $payload->data->stake;
                        $exchangeRate   = $payload->data->exchange_rate;
                        $exchangeRateId = $payload->data->exchange_rate_id;

                        ProviderAccountOrder::create([
                            'order_log_id'       => $orderLogs->id,
                            'exchange_rate_id'   => $exchangeRateId,
                            'actual_stake'       => $actualStake,
                            'actual_to_win'      => $actualStake * $order->odds,
                            'actual_profit_loss' => 0.00,
                            'exchange_rate'      => $exchangeRate,
                        ]);

                        WSOrderStatus::dispatch(
                            $row['user_id'],
                            $orderId,
                            $status,
                            $this->message->data->odds,
                            $ordersTable['orderId:' . $messageOrderId]['orderExpiry'],
                            $ordersTable['orderId:' . $messageOrderId]['created_at']
                        );

                        $topics->set('unique:' . uniqid(), [
                            'user_id'    => $row['user_id'],
                            'topic_name' => 'open-order-' . $this->message->data->bet_id
                        ]);

                        $ordersTable['orderId:' . $messageOrderId]['bet_id'] = $this->message->data->bet_id;
                        $ordersTable['orderId:' . $messageOrderId]['status'] = $status;

                        if ($payloadsTable->exists($payloadsSwtId)) {
                            $payloadsTable->del($payloadsSwtId);
                        }
                    }
                }
            }

            DB::commit();
        } catch (Exception $e) {
            Log::error(json_encode([
                'TransformKafkaMessageBet' => [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                ]
            ]));

            DB::rollBack();
        }
    }
}
