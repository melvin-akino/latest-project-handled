<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;
use Illuminate\Support\Facades\DB;

class UserBet extends Model
{
    // use LogsActivity;

    protected $table = 'user_bets';

    protected $fillable = [
        'user_id',
        'sport_id',
        'odd_type_id',
        'market_id',
        'status',
        'odds',
        'stake',
        'market_flag',
        'order_expiry',
        'odds_label',
        'ml_bet_identifier',
        'score_on_bet',
        'final_score',
        'mem_uid',
        'master_event_unique_id',
        'master_league_name',
        'master_team_home_name',
        'master_team_away_name',
        'market_providers',
        'min_odds',
        'max_odds',
        'created_at',
        'updated_at',
    ];

    public static function getPending()
    {
        return self::join('users as u', 'u.id', 'user_bets.user_id')
                ->where('user_bets.status', 'PENDING')
                ->select('user_bets.*', 'u.is_vip')
                ->get();
    }

    public static function getUserBetsByEvent(string $eventId)
    {
        return self::join('odd_types as ot', 'ot.id', 'user_bets.odd_type_id')
                ->join('sport_odd_type as sot', 'ot.id', 'sot.odd_type_id')
                ->where('user_id', auth()->user()->id)
                ->where('sot.sport_id', DB::raw('user_bets.sport_id'))
                ->where('master_event_unique_id', $eventId)
                ->whereIn('user_bets.odd_type_id', function ($query) {
                    $query->select('id')->from('odd_types')->whereIn('type', ['HDP', 'HT HDP', 'OU', 'HT OU']);
                })
                ->select([
                    'user_bets.id',
                    DB::raw("(SELECT SUM(stake) FROM provider_bets WHERE user_bet_id = user_bets.id AND status NOT IN ('PENDING', 'FAILED', 'CANCELLED', 'REJECTED', 'VOID', 'ABNORMAL BET', 'REFUNDED')) as stake"),
                    'odds',
                    'odds_label as points',
                    'user_bets.odd_type_id',
                    'sot.name as sport_odd_type_name',
                    'market_flag',
                    'master_team_home_name as home_team_name',
                    'master_team_away_name as away_team_name',
                    'user_bets.created_at',
                    'score_on_bet',
                    'final_score'
                ]);
    }

    protected static $logAttributes = [
        'user_id',
        'sport_id',
        'odd_type_id',
        'market_id',
        'status',
        'odds',
        'stake',
        'market_flag',
        'order_expiry',
        'odds_label',
        'ml_bet_identifier',
        'score_on_bet',
        'final_score',
        'mem_uid',
        'master_event_unique_id',
        'master_league_name',
        'master_team_home_name',
        'master_team_away_name',
        'market_providers',
        'min_odds',
        'max_odds',
    ];

    protected static $logOnlyDirty = true;

    protected static $logName = 'Placed User Bet';

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->put('action', ucfirst($eventName));
        $activity->properties = $activity->properties->put('ip_address', request()->ip());
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $currency = auth()->user() ? trim(Currency::getCodeById(auth()->user()->currency_id)) : "CNY";

        return ucfirst($eventName) . " User Bet on market_id: " . $this->market_id . " with $currency " . $this->stake . " @ " . $this->odds;
    }
}
