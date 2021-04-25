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
        $event         = DB::table('event_markets')->where('mem_uid', $memUID)->first();
        $masterEventId = DB::table('event_groups')->select('master_event_id')->where('event_id', $event->event_id)->first();
        $eventIds      = DB::table('event_groups')->where('master_event_id', $masterEventId->master_event_id)->pluck('event_id');
        $query         = DB::table('event_markets')
            ->whereIn('event_id', $eventIds)
            ->where('market_flag', $event->market_flag)
            ->where('odd_type_id', $event->odd_type_id)
            ->where('odd_label', $event->odd_label)
            ->whereNull('deleted_at')
            ->pluck('provider_id');

        return $query;
    }
}
