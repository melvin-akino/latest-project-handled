<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderBet extends Model
{
    protected $table = "provider_bets";

    protected $fillable = [
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public static function getTotalStake(UserBet $userBet)
    {
        return self::where('user_bet_id', $userBet->id)->sum('stake');
    }

    public static function getQueue(UserBet $userBet)
    {
        return self::where('user_bet_id', $userBet->id)
                    ->where('status', 'QUEUE')
                    ->get();
    }

    public static function getPending(UserBet $userBet)
    {
        return self::where('user_bet_id', $userBet->id)
                    ->where('status', 'PENDING')
                    ->get();
    }
}
