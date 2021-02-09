<?php

namespace App\Services;

use App\Models\MasterLeague;
use Illuminate\Support\Facades\Log;
use Exception;

class LeagueService
{
    public static function list()
    {
        try
        {
            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => MasterLeague::select(['id', 'name'])->orderBy('name', 'asc')->get()->toArray()
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
