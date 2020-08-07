<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = "orders";

    protected $fillable = [
        'master_event_market_id',
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
        'score_on_bet',
        'odd_type_id',
        'market_flag',
        'final_score',
        'master_league_name',
        'master_team_home_name',
        'master_team_away_name',
        'master_event_unique_id',
        'master_event_market_unique_id'
    ];

    protected $hidden = [];

    public function UserWallet() {
        return $this->belongsTo(App/Models/UserWallet::class, 'user_id','user_id');
    }

    public static function getAllOrders($whereClause, $page)
    {
        $whereClause[] = ['user_id', auth()->user()->id];

        return DB::table('orders')
            ->leftJoin('providers', 'providers.id', 'orders.provider_id')
            ->leftJoin('odd_types AS ot', 'ot.id', 'orders.odd_type_id')
            ->leftJoin('event_scores as es', 'es.master_event_unique_id', 'orders.master_event_unique_id')
            ->select(
                [
                    'orders.id',
                    'orders.bet_id',
                    'orders.bet_selection',
                    'orders.odds',
                    'orders.master_event_market_unique_id',
                    'orders.stake',
                    'orders.to_win',
                    'orders.created_at',
                    'orders.settled_date',
                    'orders.profit_loss',
                    'orders.status',
                    'orders.odd_label',
                    'orders.reason',
                    'orders.master_event_unique_id',
                    'es.score as current_score',
                    'ot.id AS odd_type_id',
                    'providers.alias',
                    'ml_bet_identifier',
                    'orders.final_score'
                ]
            )
            ->where($whereClause)
            ->orderBy('orders.created_at', 'desc')
            ->get()
            ->toArray();
    }

    public static function countAllOrders()
    {
        return self::where('user_id', auth()->user()->id)->count();
    }

    public static function getOrdersByEvent($eventId)
    {
        return DB::table('orders as o')
        ->leftJoin('odd_types AS ot', 'ot.id', 'o.odd_type_id')
        ->leftJoin('sport_odd_type AS sot', 'sot.odd_type_id', 'ot.id')
        ->where('sot.sport_id', DB::raw('o.sport_id'))
        ->where('user_id', auth()->user()->id)
        ->where('o.master_event_unique_id', $eventId)
        ->whereNotIn('status', ['PENDING', 'FAILED', 'CANCELLED', 'REJECTED', 'VOID', 'ABNORMAL BET', 'REFUNDED'])
        ->whereIn('o.odd_type_id', function($query) {
            $query->select('id')->from('odd_types')->whereIn('type', ['HDP', 'HT HDP', 'OU', 'HT OU']);
        })
        ->select([
            'o.id',
            'stake',
            'odds',
            'odd_label AS points',
            'o.odd_type_id',
            'o.market_flag',
            'o.master_team_home_name as home_team_name',
            'o.master_team_away_name as away_team_name',
            'o.created_at',
            'score_on_bet',
            'sot.name as sport_odd_type_name'
        ]);
    }

    public static function getOrdersByUserId(int $userId)
    {
        return DB::table('orders')->where('user_id', $userId)->get()->toArray();
    }

    public static function getBetBarData(int $userId)
    {
        return DB::table('orders AS o')
                ->leftJoin('providers AS p', 'p.id', 'o.provider_id')
                ->leftJoin('odd_types AS ot', 'ot.id', 'o.odd_type_id')
                ->leftJoin('sport_odd_type AS sot', 'sot.odd_type_id', 'ot.id')
                ->leftJoin('event_scores as es', 'es.master_event_unique_id', 'o.master_event_unique_id')
                ->distinct()
                ->where('sot.sport_id', DB::raw('o.sport_id'))
                ->where('o.user_id', $userId)
                ->whereNull('o.settled_date')
                ->select([
                    'o.id AS order_id',
                    'p.alias',
                    'o.master_event_unique_id',
                    'o.master_event_market_unique_id',
                    'o.market_flag',
                    'o.master_league_name',
                    'o.master_team_home_name',
                    'o.master_team_away_name',
                    'es.score as current_score',
                    'o.score_on_bet',
                    'ot.id AS odd_type_id',
                    'sot.name',
                    'o.odds',
                    'o.stake',
                    'o.status',
                    'o.created_at',
                    'o.order_expiry',
                    'o.odd_label'
                ])
                ->orderBy('o.created_at', 'desc')
                ->get();
    }

    public static function getOrderProviderAlias($orderId)
    {
        return DB::table('orders as o')
                ->join('providers as p', 'p.id', 'o.provider_id')
                ->where('o.id', $orderId)
                ->select('p.alias')
                ->first();
    }
}
