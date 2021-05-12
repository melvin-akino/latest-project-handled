<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBet extends Model
{
    protected $table = "user_bets";

    protected $fillable = [
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public static function getPending()
    {
        return self::join('users as u', 'u.id', 'user_bets.user_id')
                ->where('status', 'PENDING')
                ->select('user_bets.*', 'u.is_vip')
                ->get();
    }
}
