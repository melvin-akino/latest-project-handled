<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class UserBet extends Model
{
    use LogsActivity;

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
        'created_at',
        'updated_at',
    ];

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
