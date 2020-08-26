<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Support\Facades\DB;
use App\Models\ProviderBetRules;

class ProviderAccount extends Model
{
    use SoftDeletes;

    public $timestamps = false;

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

    public static function getBettingAccount($providerId, $stake, $isVIP, $eventId, $oddType, $marketFlag)
    {
        $type  = $isVIP ? "BET_VIP" : "BET_NORMAL";
        $query = self::where('credits', '>=', $stake)
                     ->where('provider_id', $providerId)
                     ->where('is_enabled', true)
                     ->where('type', $type);

        if ($query->count() == 0) {
            return null;
        } else {
            $marketFlag = strtoupper($marketFlag);

            if ($marketFlag != 'DRAW') {
                $notAllowed = $marketFlag == 'HOME' ? "AWAY" : "HOME";

                $accountCandidates = $query->orderBy('id', 'ASC')->get()->toArray();
                if ($marketFlag == 'HOME') {
                    $accountHalfCandidates = array_slice($accountCandidates, 0, ceil($query->count() / 2));
                } else {
                    $accountHalfCandidates = array_slice($accountCandidates, ceil($query->count() / 2));
                }

                # Let us get all accounts who used to bet on particular event
                $betRules        = ProviderBetRules::where('not_allowed_ground', $marketFlag)
                                                   ->where('event_id', $eventId)
                                                   ->where('odd_type_id', $oddType)->get();
                $excludeAccounts = [];

                if ($betRules->count() != 0) {

                    foreach ($betRules as $rule) {
                        array_push($excludeAccounts, $rule->provider_account_id);
                    }
                }

                if (count($excludeAccounts) != 0) {
                    $accountFinalCandidates = [];
                    foreach ($accountHalfCandidates as $accountHalfCandidate) {
                        if (!in_array($accountHalfCandidate['id'], $excludeAccounts)) {
                            $accountFinalCandidates[] = (array) $accountHalfCandidate;
                        }
                    }
                } else {
                    $accountFinalCandidates = (array) $accountHalfCandidates;
                }

                usort($accountFinalCandidates, function($a, $b) {
                    return $a['updated_at'] <=> $b['updated_at'];
                });

                $finalProvider = (object) $accountFinalCandidates[0];//$query->first();

                if ($finalProvider) {

                    $providerAccountId = $finalProvider->id;
                    $rules             = ProviderBetRules::firstOrCreate([
                        'event_id'            => $eventId,
                        'provider_account_id' => $providerAccountId,
                        'odd_type_id'         => $oddType,
                        'team_ground'         => $marketFlag,
                        'not_allowed_ground'  => $notAllowed
                    ]);
                }

                return $finalProvider;

            } else {
                $query->orderBy('updated_at', 'ASC');
                return $query->first();
            }
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
