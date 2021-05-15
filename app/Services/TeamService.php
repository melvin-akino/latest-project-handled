<?php

namespace App\Services;

use App\Models\{MasterTeam, Provider, SystemConfiguration as SC};
use Illuminate\Support\Facades\{DB, Log};
use Exception;

class TeamService
{
    public static function list()
    {
        try
        {
            $primaryProviderId = Provider::getIdFromAlias(SC::getSystemConfigurationValue('PRIMARY_PROVIDER')->value);
            $list = MasterTeam::join('team_groups as tg', 'tg.master_team_id', 'master_teams.id')
                        ->join('teams as t', 'tg.team_id', 't.id')
                        ->select('master_teams.id', DB::raw('COALESCE(master_teams.name, t.name) as name'))
                        ->where('t.provider_id', $primaryProviderId)
                        ->orderBy('t.name', 'asc')
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
