<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderBet extends Model
{
    protected $table = 'provider_bets';

    protected $fillable = [
        'user_bet_id',
        'provider_id',
        'provider_account_id',
        'provider_error_message_id',
        'status',
        'bet_id',
        'odds',
        'stake',
        'to_win',
        'profit_loss',
        'reason',
        'settled_date',
        'min',
        'max',
        'created_at',
        'updated_at',
    ];

    public static function getProviderBets(int $userBetId)
    {
        return self::join('providers as p' , 'p.id', 'provider_bets.provider_id')
                ->where('user_bet_id', $userBetId)
                ->select([
                    'provider_bets.id',
                    'user_bet_id',
                    'bet_id',
                    'p.alias as provider',
                    'stake',
                    'odds',
                    'to_win',
                    'provider_bets.status',
                    'profit_loss as pl',
                    'provider_bets.created_at'
                ])
                ->get();
    }
}
