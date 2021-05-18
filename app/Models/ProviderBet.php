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

    public static function getProviderBets(int $userBetId = null, string $memUID = null)
    {
        return self::join('providers as p' , 'p.id', 'provider_bets.provider_id')
            ->leftJoin('provider_error_messages as pem', 'pem.id', 'provider_bets.provider_error_message_id')
            ->leftJoin('error_messages as em', 'em.id', 'pem.error_message_id')
            ->join('user_bets as ub', 'ub.id', 'provider_bets.user_bet_id')
            ->when($userBetId, function($query) use($userBetId) {
                return $query->where('user_bet_id', $userBetId);
            })
            ->when($memUID, function($query) use($memUID) {
                return $query->where('ub.mem_uid', $memUID);
            })
            ->select([
                'provider_bets.id',
                'user_bet_id',
                'bet_id',
                'p.alias as provider',
                'provider_bets.stake',
                'provider_bets.odds',
                'ub.odds_label',
                'to_win',
                'provider_bets.status',
                'profit_loss as pl',
                'provider_bets.created_at',
                'reason',
                'provider_error_message_id',
                'em.error as error_message',
                'ub.market_flag',
                'ub.score_on_bet',
                'ub.master_team_home_name',
                'ub.master_team_away_name',
                'ub.master_event_unique_id',
                'ub.mem_uid'
            ])
            ->get();
    }
}
