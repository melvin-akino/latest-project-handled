<?php

namespace App\Http\Controllers;

use App\Models\Country;

class AppController extends Controller
{
    public function index()
    {
        $defaultData = [
            'price-format'               => config('constants.price-format'),
            'trade-layout'               => config('constants.trade-layout'),
            'sort-event'                 => config('constants.sort-event'),
            'betslip-adaptive-selection' => config('constants.betslip-adaptive-selection'),
            'language'                   => config('constants.language'),
            'country'                    => Country::all()->toArray()
        ];

        return view('app', [
            'default_data' => $defaultData
        ]);
    }
}
