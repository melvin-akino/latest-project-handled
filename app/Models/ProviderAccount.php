<?php

namespace App\Models;

use App\Facades\{SwooleHandler, WalletFacade};
use App\Models\{Currency, Provider};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use JsonException;

class ProviderAccount extends Model
{
    protected $table = "provider_accounts";

    use SoftDeletes;

    protected $fillable = [
        'username',
        'password',
        'type',
        'punter_percentage',
        'provider_id',
        'is_enabled',
        'is_idle',
        'credits',
        'uuid',
        'deleted_at'
    ];

    public function providers()
    {
        return $this->belongsTo('App\Models\Provider');
    }

    public static function getProviderAccounts($providerId)
    {
        return self::where('provider_id', $providerId)->get()->toArray();
    }

    public static function getUsernameId($username)
    {
        return self::where('username', $username)
            ->first()
            ->id;
    }

    public static function getUuidByUsername($username)
    {
        return self::where('username', $username)
            ->first()
            ->uuid;
    }

    public static function getBettingAccount($providerId, $stake, $isVIP, $eventId, $oddType, $marketFlag, $token)
    {
        $type     = $isVIP ? "BET_VIP" : "BET_NORMAL";
        $provider = Provider::find($providerId);
        $currency = Currency::find($provider->currency_id);
        $query    = self::where('provider_id', $providerId)
            ->where('is_enabled', true)
            ->where('type', $type)
            ->orderBy('credits', 'DESC');

        if ($query->pluck('uuid')->count() == 0) {
            return null;
        } else {
            $marketFlag = strtoupper($marketFlag);

            if ($marketFlag != 'DRAW') {
                $notAllowed        = $marketFlag == 'HOME' ? "AWAY" : "HOME";
                $batch             = WalletFacade::getBatchBalance($token, $query->pluck('uuid')->toArray(), trim(strtoupper($currency->code)));
                $uuids             = array_keys((array) $batch->data);
                $accountCandidates = $query->whereIn('uuid', $uuids)->get()->toArray();
                $betRules          = ProviderBetRules::where('not_allowed_ground', $marketFlag)
                    ->where('event_id', $eventId)
                    ->get();
                $excludeAccounts   = [];
                $reservedAccounts  = [];

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

                    if (empty($accountFinalCandidates) && !empty($accountCandidates)) {
                        $accountFinalCandidates[0] = end($accountCandidates);
                    }
                } else {
                    $accountFinalCandidates = (array) $accountCandidates;
                }

                usort($accountFinalCandidates, function ($a, $b) {
                    return $b['credits'] <=> $a['credits'];
                });

                if (count($accountFinalCandidates) > 0) {
                    $finalProvider = (object) $accountFinalCandidates[0]; //$query->first();

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
}
