<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BetWalletTransaction extends Model
{
    protected $table = "bet_wallet_transactions";

    protected $fillable = [
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
