<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use App\Models\{UserWallet, Source};
use App\Models\CRM\CrmTransfer;
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


     public function userwallet()
    {
        return $this->belongsTo(UserWallet::class, 'wallet_id');
    }
     public function source()
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    public function crm_transfer_resource()
    {
        return $this->belongsTo(CrmTransfer::class, 'id','wallet_ledger_id');
    }
}