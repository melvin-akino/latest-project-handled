<?php

namespace App\Http\Controllers;

use App\Models\{Provider, Timezones};
use Throwable;
use Exception;

class ResourceController extends Controller
{
    public function getTimezones()
    {
        $timezones = Timezones::getAll();

        return response()->json([
            'status'      => true,
            'status_code' => 200,
            'data'        => $timezones,
        ]);
    }

    public function getProviders()
    {
        try {
            $providers = Provider::getActiveProviders()->get([
                'id',
                'alias'
            ]);

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $providers
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 400,
                'message'     => trans('generic.bad-request'),
            ], 400);
        }
    }
}
