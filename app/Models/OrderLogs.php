<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLogs extends Model
{
    protected $table = "order_logs";

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'provider_id',
        'sport_id',
        'bet_id',
        'bet_selection',
        'status',
        'settled_date',
        'reason',
        'profit_loss',
        'order_id',
        'provider_account_id'
    ];

    protected $hidden = [];

    public static function getLogByRetryType(int $orderId, int $retryTypeId)
    {
        return self::leftJoin('provider_error_messages as pem', 'pem.message', 'order_logs.reason')
            ->where('pem.retry_type_id', $retryTypeId)
            ->where('order_id', $orderId)
            ->whereIn('order_logs.status', ['PENDING', 'FAILED']);
    }

}
