<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class ProviderAccount extends Model
{
    protected $table = "provider_accounts";

    protected $fillable = [];

    private static function getProviderAccount($stake, $isVIP)
    {
        if (!$isVIP) {
            $query = self::where('credits', '>=', $stake)
                ->where('type', 'BET-NORMAL');
        } else {
            $query = self::where('credits', '>=', $stake)
                ->where('type', 'BET-VIP');
        }

        $isIdle = $query->where('idle', true);

        if (!$isIdle->exists()) {
            $query = $query->orderBy('updated_at', 'ASC');
        }

        return $query->first()->username;
    }
}
