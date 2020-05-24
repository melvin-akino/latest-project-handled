<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Support\Facades\DB;
use App\Models\ProviderBetRules;

class ProviderAccount extends Model
{
    use SoftDeletes;

    protected $table = "provider_accounts";

    protected $fillable = [
        'provider_id',
        'type',
        'username',
        'password',
        'punter_percentage',
        'credits',
        'deleted_at',
        'is_idle',
        'is_enabled',
    ];

    public static function getBettingAccount($providerId, $stake, $isVIP, $eventId,$oddType,$marketFlag) 
    {
        
        $finalProvider = '';
        $type  = $isVIP ? "BET_VIP" : "BET_NORMAL";
        $query = self::where('credits', '>=', $stake)
            ->where('provider_id', $providerId)
            ->where('is_enabled', true)
            ->where('type', $type)
            ->inRandomOrder();

        $marketFlag = strtoupper($marketFlag);

        if ($marketFlag !='DRAW') {
            $notAllowed = $marketFlag =='HOME' ? "AWAY" : "HOME";
            
            # Let us get all accounts who used to bet on particular event 
            $betRules = ProviderBetRules::where('not_allowed_ground',$marketFlag)
                        ->where('event_id',$eventId)
                        ->where('odd_type_id',$oddType)->get();
            $excludeAccounts =[];

            if ($betRules->count() != 0) {

               foreach ($betRules as $rule) {
                    array_push($excludeAccounts, $rule->provider_account_id);
               }
            }
            
            if (count($excludeAccounts) != 0) {
                $query = $query->whereNotIn('id', $excludeAccounts);
            }

            $finalProvider = $query->first();
             
            if ($finalProvider)
            {

                $providerAccountId = $finalProvider->id;
                $rules = ProviderBetRules::create([
                    'event_id'              => $eventId,
                    'provider_account_id'   => $providerAccountId,
                    'odd_type_id'           => $oddType,
                    'team_ground'           => $marketFlag,
                    'not_allowed_ground'    => $notAllowed
                ]);
            }

            return $finalProvider;

        } else {

            return $query->first();
        }

    }

    public static function getProviderAccount($providerId, $stake, $isVIP)
    {

        
        $type  = $isVIP ? "BET_VIP" : "BET_NORMAL";
        $query = self::where('credits', '>=', $stake)
            ->where('provider_id', $providerId)
            ->where('is_enabled', true)
            ->where('type', $type);

        $isIdle = $query->where('is_idle', true);

        if ($isIdle->exists()) {
            $query = $query->where('is_idle', true);
        }

        $query = $query->orderBy(
            DB::raw(
                '(
                    CASE
                        WHEN is_idle = true THEN 1
                        WHEN is_idle = false THEN 2
                    END
                )'
            )
        )->orderBy('updated_at', 'ASC')
        ->orderBy('id', 'ASC');

        return $query->first();
    }

    public static function getUsernameId($username)
    {
        return self::where('username', $username)
            ->first()
            ->id;
    }
}
