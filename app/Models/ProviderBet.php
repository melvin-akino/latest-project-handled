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
        'game_schedule',
        'created_at',
        'updated_at',
    ];

    public static function getProviderBets(int $userBetId)
    {
        return self::join('providers as p' , 'p.id', 'provider_bets.provider_id')
            ->leftJoin('provider_error_messages as pem', 'pem.id', 'provider_bets.provider_error_message_id')
            ->leftJoin('error_messages as em', 'em.id', 'pem.error_message_id')
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
                'provider_bets.created_at',
                'reason',
                'provider_error_message_id',
                'em.error as error_message'
            ])
            ->get();
    }
}
