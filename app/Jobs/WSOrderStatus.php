<?php

namespace App\Jobs;

use App\Facades\SwooleHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WSOrderStatus implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $orderId, $status, $odds, $expiry, $createdAt)
    {
        $this->userId     = $userId;
        $this->orderId    = $orderId;
        $this->status     = $status;
        $this->odds       = $odds;
        $this->expiry     = $expiry;
        $this->created_at = $createdAt;
    }

    public function handle()
    {
        $swoole = app('swoole');

        $doesExist = false;
        foreach ($swoole->wsTable as $key => $value) {
            if ($key == 'uid:' . $this->userId) {
                $doesExist = true;
                break;
            }
        }
        if ($doesExist) {
            $fd = $swoole->wsTable->get('uid:' . $this->userId);

            if ($swoole->isEstablished($fd['value'])) {
                $swoole->push($fd['value'], json_encode([
                    'getOrderStatus' => [
                        'order_id' => $this->orderId,
                        'status'   => $this->status,
                        'odds'     => $this->odds
                    ]
                ]));
            }

            $forBetBarRemoval = [
                'FAILED',
                'CANCELLED',
            ];
            if (in_array(strtoupper($this->status), $forBetBarRemoval)) {
                if (time() - strtotime($this->created_at) > $this->expiry) {
                    SwooleHandler::setValue('topicTable', 'userId:' . $this->userId . ':unique:' . uniqid(), [
                        'user_id' => $this->userId,
                        'topic_name' => 'removal-bet-' . $this->orderId
                    ]);
                }
            }
        }
    }
}
