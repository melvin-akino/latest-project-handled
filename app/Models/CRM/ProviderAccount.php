<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProviderAccount extends Model
{
    protected $table = "provider_accounts";

    protected $fillable = [];

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

        return $query->first()->username;
    }
}
