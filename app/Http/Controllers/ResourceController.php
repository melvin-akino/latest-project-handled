<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
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
            Log::error($e->getMessage());
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
            $providers = Provider::getActiveProviders()->get([
                'id',
                'alias',
                'priority'
            ]);

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $providers
            ], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status'      => false,
                'status_code' => 400,
                'message'     => trans('generic.bad-request'),
            ], 400);
        }
    }
}
