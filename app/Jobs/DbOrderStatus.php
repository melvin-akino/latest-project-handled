<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Order;

class DbOrderStatus implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $orderId, $status)
    {
        $this->userId  = $userId;
        $this->orderId = $orderId;
        $this->status  = $status;
    }

    public function handle()
    {
        $swoole    = app('swoole');
        $fd      = $swoole->ws->get('uid:' . $this->userId);

        Order::where('id', $this->orderId)->update([
            'status' => $this->status
        ]);

        $swoole->push($fd['value'], json_encode([
            'getOrderStatus' => [
                'order_id' => $this->orderId,
                'status'   => $this->status
            ]
        ]));

        $forBetBarRemoval = [
            'FAILED',
            'CANCELLED',
        ];
        if (in_array(strtoupper($this->status), $forBetBarRemoval)) {
            WSForBetBarRemoval::dispatch($fd['value'], $this->orderId);
        }
    }
}
