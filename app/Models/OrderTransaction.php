<?php

namespace App\Models;

use App\User;
use App\Models\{Currency, Source};
use App\Models\WalletLedger;

use Exception;
use Illuminate\Database\Eloquent\Model;

class OrderTransaction extends Model
{
    protected $table = "order_transactions";

    public $timestamps = true;

    protected $fillable = [
        'order_logs_id',
        'user_id',
        'source_id',
        'currency_id',
        'wallet_ledger_id',
        'provider_account_id',
        'reason',
        'amount'
    ];
}
