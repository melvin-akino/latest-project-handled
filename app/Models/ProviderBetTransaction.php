<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderBetTransaction extends Model
{
    protected $table = 'provider_bet_transactions';

    protected $fillable = [
        'provider_bet_id',
        'exchange_rate_id',
        'actual_stake',
        'actual_to_win',
        'actual_profit_loss',
        'exchange_rate',
        'punter_percentage',
        'created_at',
        'updated_at',
    ];

    protected static $logAttributes = [
        'provider_bet_id',
        'exchange_rate_id',
        'actual_stake',
        'actual_to_win',
        'actual_profit_loss',
        'exchange_rate',
        'punter_percentage',
    ];
}
