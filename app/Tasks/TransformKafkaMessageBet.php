<?php

namespace App\Tasks;

use App\Jobs\WSOrderStatus;
use App\Models\Order;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\DB;

class TransformKafkaMessageBet extends Task
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function handle()
    {
        $swoole = app('swoole');

        $topics        = $swoole->topicTable;
        $ordersTable   = $swoole->ordersTable;
        $payloadsTable = $swoole->payloadsTable;

        foreach ($topics AS $key => $row) {
            if (strpos($row['topic_name'], 'order-') === 0) {
                $orderId         = substr($row['topic_name'], strlen('order-'));
                $requestUIDArray = explode('-', $this->message->request_uid);
                $messageOrderId  = end($requestUIDArray);

                if ($orderId == $messageOrderId) {
                    $status = strtoupper($this->message->data->status);

                    if ($status == 'FAILED') {
                        continue;
                    }

                    $order  = Order::updateOrCreate([
                        'id' => $messageOrderId
                    ], [
                        'bet_id' => $this->message->data->bet_id,
                        'reason' => $this->message->data->reason,
                        'status' => $status,
                        'odds'   => $this->message->data->odds
                    ]);

                    $betSelectionArray         = explode("\n", $order->bet_selection);
                    $betSelectionTeamOddsArray = explode('@ ', $betSelectionArray[1]);
                    $updatedOrderOdds          = $betSelectionTeamOddsArray[0] . '@ ' . number_format($order->odds, 2);
                    $order->bet_selection      = implode("\n", [
                        $betSelectionArray[0],
                        $updatedOrderOdds,
                        $betSelectionArray[2]
                    ]);

                    $order->to_win             = $order->stake * $this->message->data->odds;
                    $order->actual_to_win      = $order->actual_stake * $this->message->data->odds;
                    $order->save();

                    DB::table('order_logs')
                        ->insert([
                            'provider_id'   => $order->provider_id,
                            'sport_id'      => $order->sport_id,
                            'bet_id'        => $this->message->data->bet_id,
                            'bet_selection' => $order->bet_selection,
                            'status'        => $status,
                            'user_id'       => $order->user_id,
                            'reason'        => $this->message->data->reason,
                            'profit_loss'   => $order->profit_loss,
                            'order_id'      => $order->id,
                            'settled_date'  => '',
                            'created_at'    => Carbon::now(),
                            'updated_at'    => Carbon::now(),
                        ]);

                    WSOrderStatus::dispatch(
                        $row['user_id'],
                        $orderId,
                        $status,
                        $this->message->data->odds,
                        $ordersTable['orderId:' . $orderId]['orderExpiry'],
                        $ordersTable['orderId:' . $orderId]['created_at']
                    );

                    $topics->set('unique:' . uniqid(), [
                        'user_id'    => $row['user_id'],
                        'topic_name' => 'open-order-' . $this->message->data->bet_id
                    ]);

                    $ordersTable['orderId:' . $orderId]['bet_id'] = $this->message->data->bet_id;
                    $ordersTable['orderId:' . $orderId]['status'] = $status;

                    $payloadsSwtId = implode(':', [
                        "place-bet-" . $orderId,
                        "uId:" . $row['user_id'],
                        "mId:" . $order->market_id
                    ]);
                    if ($payloadsTable->exists($payloadsSwtId)) {
                        $payloadsTable->del($payloadsSwtId);
                    }
                }
            }
        }
    }
}
