<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\{DB, Log};
use App\Models\{Provider, Timezones};
use Exception;

class ResourceController extends Controller
{
    public function getTimezones()
    {
        try {
            $timezones = Timezones::getAll();

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $timezones,
            ]);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "ResourceController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    public function getProviders()
    {
        try {
            $providers = Provider::getActiveProviders()
                ->get([
                    'id',
                    'alias',
                    DB::raw("(
                        CASE
                            WHEN alias = (SELECT UPPER(\"value\") FROM system_configurations WHERE \"type\" = 'PRIMARY_PROVIDER')::text THEN true
                            WHEN alias != (SELECT UPPER(\"value\") FROM system_configurations WHERE \"type\" = 'PRIMARY_PROVIDER')::text THEN false
                        END
                    ) AS is_primary")
                ]);

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $providers
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "ResourceController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 400,
                'message'     => trans('generic.bad-request'),
            ], 400);
        }
    }
}
