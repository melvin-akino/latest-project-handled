<?php

namespace App\Http\Controllers;

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
        ];


        return view('app', [
            'default_data' => $defaultData
        ]);
    }
}
