<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = "orders";

    protected $fillable = [
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
        'master_event_market_unique_id',
        'provider_error_message_id'
    ];

    protected $hidden = [];

    public function UserWallet()
    {
        return $this->belongsTo(App / Models / UserWallet::class, 'user_id', 'user_id');
    }

    public static function getAllOrders($whereClause, $page)
    {
        $whereClause[] = ['user_id', auth()->user()->id];

        return DB::table('orders')
                 ->leftJoin('providers', 'providers.id', 'orders.provider_id')
                 ->leftJoin('odd_types AS ot', 'ot.id', 'orders.odd_type_id')
                 ->leftJoin('event_scores as es', 'es.master_event_unique_id', 'orders.master_event_unique_id')
                 ->leftJoin('provider_error_messages As pe','pe.id','orders.provider_error_message_id')
                 ->leftJoin('error_messages as em', 'em.id','pe.error_message_id')
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
                         'orders.final_score',
                         'orders.market_flag',
                         'orders.master_team_home_name',
                         'orders.master_team_away_name',
                         'em.error as multiline_error'
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

    public static function getOrdersByEvent($eventId, $betMatrix = false, $halfTime = false)
    {
        return DB::table('orders as o')
                 ->leftJoin('odd_types AS ot', 'ot.id', 'o.odd_type_id')
                 ->leftJoin('sport_odd_type AS sot', 'sot.odd_type_id', 'ot.id')
                 ->leftJoin('providers as p', 'o.provider_id', 'p.id')
                 ->where('sot.sport_id', DB::raw('o.sport_id'))
                 ->where('user_id', auth()->user()->id)
                 ->where('o.master_event_unique_id', $eventId)
                 ->when($betMatrix, function($query) {
                     return $query->whereNotIn('status', ['PENDING', 'FAILED', 'CANCELLED', 'REJECTED', 'VOID', 'ABNORMAL BET', 'REFUNDED']);
                 })
                 ->when($betMatrix && !$halfTime, function($query) {
                    return $query->where('ot.type', 'NOT LIKE', '%HT%');
                 })
                 ->when($betMatrix && $halfTime, function($query) {
                    return $query->where('ot.type', 'LIKE', '%HT%');
                 })
                 ->orderBy('o.created_at', 'DESC')
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
                     'sot.name as sport_odd_type_name',
                     'p.alias as provider',
                     'o.status',
                     'final_score',
                     'ot.type as odd_type'
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
                 ->leftJoin('provider_error_messages as pem', 'o.provider_error_message_id', 'pem.id')
                 ->leftJoin('error_messages as em', 'pem.error_message_id', 'em.id')
                 ->leftJoin('retry_types as rt', 'pem.retry_type_id', 'rt.id')
                 ->distinct()
                 ->where('sot.sport_id', DB::raw('o.sport_id'))
                 ->where('o.user_id', $userId)
                 ->whereNull('o.settled_date')
                 ->whereIn('o.status', ['PENDING', 'SUCCESS', 'FAILED'])
                 ->select([
                     'o.id AS order_id',
                     'p.alias',
                     'o.master_event_unique_id',
                     'o.master_event_market_unique_id',
                     'o.market_flag',
                     'o.master_league_name',
                     'o.master_team_home_name',
                     'o.master_team_away_name',
                     'o.score_on_bet',
                     'ot.id AS odd_type_id',
                     'ot.type as odd_type',
                     'sot.name as odd_type_name',
                     'o.odds',
                     'o.stake',
                     'o.status',
                     'o.created_at',
                     'o.order_expiry',
                     'o.odd_label',
                     'em.error',
                     'o.reason',
                     'rt.type as retry_type',
                     'pem.odds_have_changed'
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

    public static function getEventMarketBetSlipDetails($memUID)
    {
        return DB::table('event_markets AS em')
            ->join('events AS e', 'e.id', 'em.event_id')
            ->join('event_groups AS eg', 'eg.event_id', 'e.id')
            ->join('master_events AS me', 'me.id', 'eg.master_event_id')
            ->where('em.mem_uid', $memUID)
            ->select([
                'em.is_main',
                'em.market_flag',
                'em.odd_type_id',
                'me.id AS master_event_id'
            ]);
    }

    public static function getEventBetSlipDetails($masterEventId)
    {
        $primaryProvider = Provider::getIdFromAlias(SystemConfiguration::getSystemConfigurationValue('PRIMARY_PROVIDER')->value);

        return DB::table('master_events as me')
                ->join('event_groups as eg', 'me.id', 'eg.master_event_id')
                ->join('events as e', 'eg.event_id', 'e.id')
                ->join('master_leagues as ml', 'ml.id', 'me.master_league_id')
                ->join('league_groups as lg', 'ml.id', 'lg.master_league_id')
                ->join('leagues as l', function ($join) use($primaryProvider) {
                    $join->on('l.id', 'lg.league_id');
                    $join->where('l.provider_id', $primaryProvider);
                })
                ->join('master_teams as ht', 'ht.id', 'me.master_team_home_id')
                ->join('team_groups AS tgh', 'tgh.master_team_id', 'ht.id')
                ->join('teams AS th', function ($join) use($primaryProvider) {
                    $join->on('th.id','tgh.team_id');
                    $join->where('th.provider_id', $primaryProvider);
                })
                ->join('master_teams as at', 'at.id', 'me.master_team_away_id')
                ->join('team_groups AS tga', 'tga.master_team_id', 'at.id')
                ->join('teams AS ta', function ($join) use($primaryProvider) {
                    $join->on('ta.id','tga.team_id');
                    $join->where('ta.provider_id', $primaryProvider);
                })
                ->where('me.id', $masterEventId)
                ->select([
                    DB::raw('COALESCE(ml.name, l.name) as league_name'),
                    DB::raw('COALESCE(ht.name, th.name) as home_team_name'),
                    DB::raw('COALESCE(at.name, ta.name) as away_team_name'),
                    'master_event_unique_id',
                    'e.game_schedule',
                    'e.ref_schedule',
                    'e.running_time',
                    'e.score',
                    'e.home_penalty',
                    'e.away_penalty',
                    'me.sport_id'
                ]);
    }

    public static function getEventMarketDetails($orderId) {
        return self::join('event_markets as em', 'em.bet_identifier', 'orders.market_id')
            ->join('events as e', 'e.id', 'em.event_id')
            ->join('odd_types as ot', 'ot.id', 'em.odd_type_id')
            ->select([
                'em.event_id',
                'em.odd_type_id',
                'em.odd_label',
                'em.market_flag',
                'em.is_main',
                'em.market_event_identifier',
                DB::raw('CONCAT(ot.type, em.market_flag, em.odd_label) as market_common')
            ])
            ->where('orders.id', $orderId)
            ->whereNull('e.deleted_at')
            ->first();
    }

    public static function getEventDetails($orderId) {
        return self::join('event_markets as em', 'em.bet_identifier', 'orders.market_id')
            ->join('events as e', 'e.id', 'em.event_id')
            ->join('event_groups as eg', 'eg.event_id', 'e.id')
            ->join('master_events as me', 'me.id', 'eg.master_event_id')
            ->select([
                'me.master_league_id',
                'me.master_event_unique_id',
                'me.id as master_event_id',
                'e.game_schedule'
            ])
            ->where('orders.id', $orderId)
            ->first();
    }

    public static function retryBetData($orderId, bool $newBet = false)
    {
        $newBet = $newBet ?: 'null';

        return self::join('order_logs AS ol', 'ol.order_id', 'orders.id')
            ->join('provider_account_orders AS pao', function ($join) {
                $join->on('pao.order_log_id', 'ol.id');
                $join->where('ol.status', 'PENDING');
            })
            ->join('providers AS p', 'p.id', 'orders.provider_id')
            ->join('event_markets AS em', 'em.bet_identifier', 'orders.market_id')
            ->leftJoin('provider_accounts AS pa', 'pa.id', 'orders.provider_account_id')
            ->select([
                'orders.*',
                'pao.actual_stake',
                'pa.username',
                'pa.line',
                'p.alias',
                'em.event_id',
                DB::raw("null AS retry_type_id"),
                DB::raw("$newBet AS new_bet")
            ])
            ->where('orders.id', $orderId)
            ->first();
    }
}
