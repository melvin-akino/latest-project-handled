<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class ProviderBet extends Model
{
    use LogsActivity;

    protected $table = 'provider_bets';

    protected $fillable = [
        'user_bet_id',
        'provider_id',
        'provider_account_id',
        'provider_error_message_id',
        'status',
        'bet_id',
        'odds',
        'stake',
        'to_win',
        'profit_loss',
        'reason',
        'settled_date',
        'min',
        'max',
        'game_schedule',
        'created_at',
        'updated_at',
    ];

    protected static $logAttributes = [
        'user_bet_id',
        'provider_id',
        'provider_account_id',
        'provider_error_message_id',
        'status',
        'bet_id',
        'odds',
        'stake',
        'to_win',
        'profit_loss',
        'reason',
        'settled_date',
        'min',
        'max',
        'game_schedule',
    ];

    protected static $logOnlyDirty = true;

    protected static $logName = 'Placed Provider Bet';

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->put('action', ucfirst($eventName));
        $activity->properties = $activity->properties->put('ip_address', request()->ip());
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $provider = Provider::find($this->provider_id);
        $currency = trim(Currency::getCodeById($provider->currency_id));

        if ($this->settled_date) {
            return "Settled Provider Bet on " . $provider->alias . " with $currency " . $this->stake . " @ " . $this->odds . ". " . $this->status . " $currency " . $this->profit_loss;
        } else if ($this->provider_error_message_id) {
            return ucfirst($eventName) . " Provider Bet on " . $provider->alias . ": " . $this->reason;
        }

        return ucfirst($eventName) . " Provider Bet on " . $provider->alias . " with $currency " . $this->stake . " @ " . $this->odds;
    }

    public static function getProviderBets(int $userBetId)
    {
        return self::join('providers as p' , 'p.id', 'provider_bets.provider_id')
            ->leftJoin('provider_error_messages as pem', 'pem.id', 'provider_bets.provider_error_message_id')
            ->leftJoin('error_messages as em', 'em.id', 'pem.error_message_id')
            ->where('user_bet_id', $userBetId)
            ->select([
                'provider_bets.id',
                'user_bet_id',
                'bet_id',
                'p.alias as provider',
                'stake',
                'odds',
                'to_win',
                'provider_bets.status',
                'profit_loss as pl',
                'provider_bets.created_at',
                'reason',
                'provider_error_message_id',
                'em.error as error_message'
            ])
            ->get();
    }
}
