<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\UserProviderConfiguration;
class Provider extends Model
{
    protected $table = 'providers';

    protected $fillable = [
        'name',
        'alias',
        'punter_percentage',
        'priority',
        'is_enabled'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public static function getActiveProviders()
    {
        return self::where('is_enabled', true)
            ->orderBy('priority', 'asc');
    }

    public static function getAllProviders()
    {
        return self::orderBy('priority', 'asc')->orderBy('id', 'asc')->get()->toArray();
    }

    public static function getLatestPriority()
    {
        return self::orderBy('priority', 'desc')->get()->first();
    }

    public static function getIdFromAlias($alias)
    {
        $query = self::where('alias', strtoupper($alias));

        if ($query->exists()) {
            return $query->first()->id;
        }
    }

    public static  function getMostPriorityProvider(int $userId)
    {
        $userProvider = UserProviderConfiguration::where('user_id', $userId)
            ->join('providers', 'provider_id', 'providers.id');
        if ($userProvider->exists()) {
            $userProvider = $userProvider->where('active', true)->orderBy('priority', 'ASC');
            $userProvider = $userProvider->first()->provider_id;
        } else {
            $userProvider = self::where('is_enabled', true)->orderBy('priority', 'ASC');
            $userProvider = $userProvider->first()->id;
        }
        return $userProvider;
    }
}
