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
            ->select('orders.id', 'orders.bet_id', 'orders.bet_selection', 'providers.alias', 'orders.odds', 'ml_bet_identifier', 'orders.master_event_market_unique_id', 'orders.stake', 'orders.to_win', 'orders.created_at', 'orders.settled_date', 'orders.profit_loss', 'orders.status', 'me.master_event_unique_id', 'me.master_home_team_name', 'me.master_away_team_name', 'me.score', 'ot.id AS odd_type_id', 'mem.market_flag', 'orders.odd_label', 'orders.reason')
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

    public static function getOrdersByEvent($event_id)
    {
        return DB::table('orders')
        ->join('master_event_markets AS mem', 'mem.master_event_market_unique_id', 'orders.master_event_market_unique_id')
        ->join('master_events AS me', 'me.master_event_unique_id', 'mem.master_event_unique_id')
        ->where('user_id', auth()->user()->id)
        ->where('mem.master_event_unique_id', $event_id)
        ->whereNotIn('status', ['PENDING', 'FAILED', 'CANCELLED', 'REJECTED'])
        ->whereIn('mem.odd_type_id', function($query) {
            $query->select('id')->from('odd_types')->whereIn('type', ['HDP', 'HT HDP', 'OU', 'HT OU']);
        })
        ->select('orders.id', 'stake', 'odds', 'odd_label AS points', 'mem.odd_type_id', 'orders.created_at');
    }
}
