<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;
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

    public function UserWallet() {
        return $this->belongsTo(App/Models/UserWallet::class, 'user_id','user_id');
    }

    public static function getAllOrders($whereClause, $page, $limit)
    {
        $whereClause[] = ['user_id', auth()->user()->id];

        return DB::table('orders')
            ->join('providers', 'orders.provider_id', '=', 'providers.id')
            ->select('orders.bet_id', 'orders.bet_selection', 'providers.alias', 'orders.odds', 'orders.stake', 'orders.to_win', 'orders.created_at', 'orders.settled_date', 'orders.profit_loss')
            ->where($whereClause)
            ->orderBy('created_at', 'desc')
            ->limit($limit)->offset(($page - 1) * $limit)
            ->get()
            ->toArray();
    }

    public static function countAllOrders()
    {
        return self::where('user_id', auth()->user()->id)->count();
    }
}