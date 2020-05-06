<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Support\Facades\DB;

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
        )->orderBy('updated_at', 'ASC');

        return $query->first();
    }

    public static function getUsernameId($username)
    {
        return self::where('username', $username)
            ->first()
            ->id;
    }
}
