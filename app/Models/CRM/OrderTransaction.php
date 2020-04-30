<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use App\Models\{OrderLogs, Currency};
class OrderTransaction extends Model
{
    protected $table = "order_transactions";

    protected $fillable = [
        'order_logs_id',
        'user_id',
        'source_id',
        'currency_id',
        'wallet_ledger_id',
        'provider_account_id',
        'reason',
        'amount',
    ];


    public function transaction_log()
    {
        return $this->belongsTo(OrderLogs::class,'order_logs_id','id');

    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
