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
            ->leftJoin('master_event_markets AS mem', 'mem.id', 'orders.master_event_market_id')
            ->leftJoin('master_events AS me', 'me.id', 'mem.master_event_id')
            ->leftJoin('odd_types AS ot', 'ot.id', 'mem.odd_type_id')
            ->select(
                [
                    'orders.id',
                    'orders.bet_id',
                    'orders.bet_selection',
                    'orders.odds',
                    'mem.master_event_market_unique_id',
                    'orders.stake',
                    'orders.to_win',
                    'orders.created_at',
                    'orders.settled_date',
                    'orders.profit_loss',
                    'orders.status',
                    'orders.odd_label',
                    'orders.reason',
                    'me.master_event_unique_id',
                    'me.score',
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
        return DB::table('orders')
        ->leftJoin('master_event_markets AS mem', 'mem.id', 'orders.master_event_market_id')
        ->leftJoin('master_events AS me', 'me.id', 'mem.master_event_id')
        ->leftJoin('odd_types as ot', 'ot.id', 'mem.odd_type_id')
        ->leftJoin('sport_odd_type as sot', function ($join) {
            $join->on('sot.odd_type_id', '=', 'ot.id');
            $join->on('sot.sport_id', '=', 'me.sport_id');
        })
        ->leftJoin('master_teams as ht', 'ht.id', 'me.master_team_home_id')
        ->leftJoin('master_teams as at', 'at.id', 'me.master_team_away_id')
        ->where('user_id', auth()->user()->id)
        ->where('me.master_event_unique_id', $eventId)
        ->whereNotIn('status', ['PENDING', 'FAILED', 'CANCELLED', 'REJECTED', 'VOID', 'ABNORMAL BET', 'REFUNDED'])
        ->whereIn('mem.odd_type_id', function($query) {
            $query->select('id')->from('odd_types')->whereIn('type', ['HDP', 'HT HDP', 'OU', 'HT OU']);
        })
        ->select('orders.id', 'stake', 'odds', 'odd_label AS points', 'mem.odd_type_id', 'mem.market_flag', 'ht.name as home_team_name', 'at.name as away_team_name', 'orders.created_at', 'score_on_bet', 'sot.name as sport_odd_type_name');
    }

    public static function getOrdersByUserId(int $userId)
    {
        return DB::table('orders')->where('user_id', $userId)->get()->toArray();
    }

    public static function getBetBarData(int $userId)
    {
        return DB::table('orders AS o')
                ->leftJoin('providers AS p', 'p.id', 'o.provider_id')
                ->leftJoin('master_event_markets AS mem', 'mem.id', 'o.master_event_market_id')
                ->leftJoin('master_events AS me', 'me.id', 'mem.master_event_id')
                ->leftJoin('master_leagues as ml', 'ml.id', 'me.master_league_id')
                ->leftJoin('master_teams as mth', 'mth.id', 'me.master_team_home_id')
                ->leftJoin('master_teams as mta', 'mta.id', 'me.master_team_away_id')
                ->leftJoin('odd_types AS ot', 'ot.id', 'mem.odd_type_id')
                ->leftJoin('sport_odd_type AS sot', 'sot.odd_type_id', 'ot.id')
                ->distinct()
                ->where('sot.sport_id', DB::raw('o.sport_id'))
                ->where('o.user_id', $userId)
                ->whereNull('o.settled_date')
                ->select([
                    'o.id AS order_id',
                    'p.alias',
                    'mem.master_event_market_unique_id',
                    'me.master_event_unique_id',
                    'ml.name as master_league_name',
                    'mth.name as master_home_team_name',
                    'mta.name as master_away_team_name',
                    'me.score',
                    'o.score_on_bet',
                    'me.game_schedule',
                    'mem.market_flag',
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
