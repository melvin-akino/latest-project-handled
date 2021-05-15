<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BetWalletTransaction extends Model
{
    protected $table = 'bet_wallet_transactions';

    protected $fillable = [
        'provider_bet_log_id',
        'user_id',
        'source_id',
        'currency_id',
        'wallet_ledger_id',
        'provider_account_id',
        'reason',
        'amount',
        'created_at',
        'updated_at',
    ];

    protected static $logAttributes = [
        'provider_bet_log_id',
        'user_id',
        'source_id',
        'currency_id',
        'wallet_ledger_id',
        'provider_account_id',
        'reason',
        'amount',
    ];
}
