<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WSOrderStatus implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $orderId, $status, $odds, $expiry, $createdAt)
    {
        $this->userId  = $userId;
        $this->orderId = $orderId;
        $this->status  = $status;
        $this->odds    = $odds;
        $this->expiry  = $expiry;
        $this->created_at = $createdAt;
    }

    public function handle()
    {
        $swoole = app('swoole');
        $fd     = $swoole->wsTable->get('uid:' . $this->userId);

        $swoole->push($fd['value'], json_encode([
            'getOrderStatus' => [
                'order_id' => $this->orderId,
                'status'   => $this->status,
                'odds'     => $this->odds
            ]
        ]));

        $forBetBarRemoval = [
            'FAILED',
            'CANCELLED',
        ];
        if (in_array(strtoupper($this->status), $forBetBarRemoval)) {
            if (time() - strtotime($this->created_at) > $this->expiry) {
                WSForBetBarRemoval::dispatch($fd['value'], $this->orderId);
            }
        }
    }
}
