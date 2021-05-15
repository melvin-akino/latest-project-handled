<?php

namespace App\Services;

use App\Models\{MasterLeague, Provider, SystemConfiguration as SC };
use Illuminate\Support\Facades\{DB, Log};
use Exception;

class LeagueService
{
    public static function list()
    {
        try
        {
            $primaryProviderId = Provider::getIdFromAlias(SC::getSystemConfigurationValue('PRIMARY_PROVIDER')->value);
            $list = MasterLeague::join('league_groups as lg', 'lg.master_league_id', 'master_leagues.id')
                        ->join('leagues as l', 'lg.league_id', 'l.id')
                        ->select('master_leagues.id', DB::raw('COALESCE(master_leagues.name, l.name) as name'), 'is_priority')
                        ->where('l.provider_id', $primaryProviderId)
                        ->orderBy('is_priority', 'desc')
                        ->orderBy('l.name', 'asc')
                        ->get()
                        ->toArray();

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $list
            ], 200);
        }
        catch (Exception $e)
        {
            Log::info('Pulling of leagues list failed.');
            Log::error($e->getMessage());
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'error'       => trans('responses.internal-error')
            ], 500);
        }
    }
}
