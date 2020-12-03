<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use App\Models\{Currency, WalletLedger};

class CrmTransfer extends Model
{
    //
    protected $table = 'crmtransfer';

    protected $fillable = [
        'transfer_amount',
        'currency_id',
        'crm_user_id',
        'reason',
        'user_id',
        'wallet_ledger_id'
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'crm_user_id', 'id');
    }

    public function wallet_ledger()
    {
        return $this->hasMany(WalletLedger::class, 'resource_id', 'crm_transfer_id');
    }
}
