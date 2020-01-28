<?php

namespace App\Http\Controllers;

use App\Models\{City, State, Timezones};
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

    public function getStates(string $countryId)
    {
        try {
            $states = State::where('country_id', $countryId)
                ->get();

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $states,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'      => true,
                'status_code' => 400,
                'message'     => trans('generic.bad-request'),
            ]);
        }
    }

    public function getCities(string $stateId)
    {
        try {
            $cities = City::where('state_id', $stateId)
                ->get();

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $cities,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'      => true,
                'status_code' => 400,
                'message'     => trans('generic.bad-request'),
            ]);
        }
    }
}
