<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\UserProviderConfiguration;
use Illuminate\Support\Facades\DB;
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
        return self::where('is_enabled', true);
    }

    public static function getAllProviders()
    {
        return self::orderBy('id', 'asc')->get()->toArray();
    }

    public static function getLatestPriority()
    {
        return self::get()->first();
    }

    public static function getIdFromAlias($alias)
    {
        $query = self::where('alias', strtoupper($alias));

        if ($query->exists()) {
            return $query->first()->id;
        }
    }

    public static function getProvidersByMemUID(string $memUID)
    {
        return DB::table('event_markets as em')
            ->join('event_market_groups as emg', 'em.id', 'emg.event_market_id')
            ->join('master_event_markets as mem', 'emg.master_event_market_id', 'mem.id')
            ->where('mem.master_event_market_unique_id', $memUID)
            ->whereNull('em.deleted_at')
            ->distinct()
            ->pluck('em.provider_id');
    }

}
