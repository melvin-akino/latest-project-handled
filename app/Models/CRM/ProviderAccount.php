<?php

namespace App\Models\CRM;

use App\Models\SystemConfiguration;
use Carbon\Carbon;
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

                $accountCandidates = $query->orderBy('credits', 'DESC')->orderBy('updated_at', 'ASC')->get()->toArray();

                # Let us get all accounts who used to bet on particular event
                $betRules         = ProviderBetRules::where('not_allowed_ground', $marketFlag)
                                                    ->where('event_id', $eventId)
                                                    ->get();
                $excludeAccounts  = [];
                $reservedAccounts = [];

                if ($betRules->count() != 0) {
                    foreach ($betRules as $rule) {
                        array_push($excludeAccounts, $rule->provider_account_id);
                    }
                } else {
                    $reservedPercentage = SystemConfiguration::getSystemConfigurationValue('PROVIDER_ACCOUNT_RESERVATION_PERCENTAGE')->value;
                    $count              = count($accountCandidates) * ($reservedPercentage / 100);
                    $reservedAccounts   = array_slice($accountCandidates, -1 * ((int) $count));
                    $accountCandidates  = array_slice($accountCandidates, 0, count($accountCandidates) - (int) $count);
                }

                $accountFinalCandidates = [];
                if (count($excludeAccounts) != 0) {
                    $nowTime = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now()->format('Y-m-d H:i:s'));
                    foreach ($accountCandidates as $accountCandidate) {
                        if (!in_array($accountCandidate['id'], $excludeAccounts)) {
                            $updatedAt                          = Carbon::createFromFormat('Y-m-d H:i:s', $accountCandidate['updated_at']);
                            $providerAccountUsageSecondsChecker = (floor((count($accountCandidates) / 10)) + 1) * 5;
                            if ($nowTime->diffInSeconds(Carbon::parse($updatedAt)) > $providerAccountUsageSecondsChecker) {
                                $accountFinalCandidates[] = (array) $accountCandidate;
                            }
                        }
                    }

                    if (empty($accountFinalCandidates) && !empty($accountCandidates))  {
                        $accountFinalCandidates[0] = $accountCandidates[end($accountCandidates)];
                    }

                } else {
                    $accountFinalCandidates = (array) $accountCandidates;
                }

                usort($accountFinalCandidates, function ($a, $b) {
                    return $b['credits'] <=> $a['credits'];
                });

                if (count($accountFinalCandidates) > 0) {

                    $finalProvider = (object) $accountFinalCandidates[0];//$query->first();

                    if ($finalProvider) {

                        $providerAccountId = $finalProvider->id;

                        foreach ($reservedAccounts as $reservedAccount) {
                            ProviderBetRules::firstOrCreate([
                                'event_id'            => $eventId,
                                'provider_account_id' => $reservedAccount['id'],
                                'odd_type_id'         => $oddType,
                                'team_ground'         => $notAllowed,
                                'not_allowed_ground'  => $marketFlag
                            ]);
                        }

                        ProviderBetRules::firstOrCreate([
                            'event_id'            => $eventId,
                            'provider_account_id' => $providerAccountId,
                            'odd_type_id'         => $oddType,
                            'team_ground'         => $marketFlag,
                            'not_allowed_ground'  => $notAllowed
                        ]);
                    }

                    return $finalProvider;
                } else {
                    return null;
                }
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
