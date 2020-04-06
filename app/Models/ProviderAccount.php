<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderAccount extends Model
{
    protected $table = "provider_accounts";

    protected $fillable = [
        'username',
        'password',
        'type',
        'punter_percentage',
        'provider_id',
        'is_enabled',
        'is_idle'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function providers()
    {
        return $this->belongsTo('App\Models\Provider');
    }

    public static function getProviderAccounts($providerId) {
    	return self::where('provider_id', $providerId)->get()->toArray();
    }
}
