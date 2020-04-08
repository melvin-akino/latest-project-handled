<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class OrderTransaction extends Model
{
    protected $table = "order_transaction";

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
}
