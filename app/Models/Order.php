<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{

    protected $table = "orders";

    protected $fillable = [
        'master_event_market_unique_id',
        'user_id',
        'market_id',
        'status',
        'bet_id',
        'bet_selection',
        'provider_id',
        'sport_id',
        'odds',
        'stake',
        'actual_stake',
        'to_win',
        'actual_to_win',
        'settled_date',
        'reason',
        'profit_loss',
    ];

    protected $hidden = [];

    public static function getAllOrders($whereClause, $page, $limit)
    {
        $whereClause[] = ['user_id', auth()->user()->id]);
        return self::where($whereClause)->orderBy('created_at', 'desc')->limit($limit)->offset(($page - 1) * $limit)->get();
    }
    public static function countAllOrders()
    {
        return self::where('user_id', auth()->user()->id])->count();
    }
}

