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
        'odd_label',
        'stake',
        'actual_stake',
        'to_win',
        'actual_to_win',
        'settled_date',
        'reason',
        'profit_loss',
        'provider_account_id',
        'order_expiry',
        'ml_bet_identifier',
    ];

    protected $hidden = [];

    public function UserWallet() {
        return $this->belongsTo(App/Models/UserWallet::class, 'user_id','user_id');
    }

    public static function getAllOrders($whereClause, $page, $limit)
    {
        $whereClause[] = ['user_id', auth()->user()->id];

        return DB::table('orders')
            ->join('providers', 'providers.id', 'orders.provider_id')
            ->join('master_event_markets AS mem', 'mem.master_event_market_unique_id', 'orders.master_event_market_unique_id')
            ->join('master_events AS me', 'me.master_event_unique_id', 'mem.master_event_unique_id')
            ->join('odd_types AS ot', 'ot.id', 'mem.odd_type_id')
            ->select('orders.id', 'orders.bet_id', 'orders.bet_selection', 'providers.alias', 'orders.odds', 'orders.master_event_market_unique_id', 'orders.stake', 'orders.to_win', 'orders.created_at', 'orders.settled_date', 'orders.profit_loss', 'orders.status', 'me.master_event_unique_id', 'me.master_home_team_name', 'me.master_away_team_name', 'me.score', 'ot.id AS odd_type_id', 'mem.market_flag', 'orders.odd_label', 'orders.reason')
            ->where($whereClause)
            ->orderBy('orders.created_at', 'desc')
            ->limit($limit)->offset(($page - 1) * $limit)
            ->get()
            ->toArray();
    }

    public static function countAllOrders()
    {
        return self::where('user_id', auth()->user()->id)->count();
    }
}
