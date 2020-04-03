<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class WalletLedger extends Model
{
     protected $table    = 'wallet_ledger';
     protected $fillable = [
        'wallet_id',
        'source_id',
        'debit',
        'credit',
        'balance',
        
    ];
}