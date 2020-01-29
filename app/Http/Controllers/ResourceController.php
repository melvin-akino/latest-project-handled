<?php

namespace App\Http\Controllers;

use App\Models\{City, Provider, State, Timezones};
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

    public function getStates($countryId)
    {
        try {
            if (!is_numeric($countryId)) {
                throw new Exception(trans('generic.bad-request'));
            }
            $states = State::select('id', 'state_name')->where('country_id', $countryId)
                ->get();

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $states,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 400,
                'message'     => trans('generic.bad-request'),
            ], 400);
        }
    }

    public function getCities($stateId)
    {
        try {
            if (!is_numeric($stateId)) {
                throw new Exception(trans('generic.bad-request'));
            }

            $cities = City::select('id', 'city_name')->where('state_id', $stateId)
                ->get();

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $cities,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 400,
                'message'     => trans('generic.bad-request'),
            ], 400);
        }
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
