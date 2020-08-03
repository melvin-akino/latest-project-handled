<?php

namespace App\Http\Controllers;

use App\Facades\SwooleHandler;
use App\Models\{ Country, Provider };
use Illuminate\Http\Request;

class AppController extends Controller
{
    public function index()
    {
        $swoole      = app('swoole');
        $defaultData = [
            'price-format'               => config('constants.price-format'),
            'trade-layout'               => config('constants.trade-layout'),
            'sort-event'                 => config('constants.sort-event'),
            'betslip-adaptive-selection' => config('constants.betslip-adaptive-selection'),
            'language'                   => config('constants.language'),
            'country'                    => Country::select('id', 'country_name')->get()->toArray()
        ];

        $providers = Provider::all();

        foreach ($providers AS $provider) {
            if (SwooleHandler::doesExistValue('maintenanceTable', 'provider', strtoupper($provider->alias))) {
                $maintenance = $swoole->maintenanceTable->get('maintenance:' . strtolower($provider->alias));

                foreach ($swoole->wsTable AS $key => $row) {
                    if ((strpos($key, 'uid:') === 0) && ($swoole->isEstablished($swoole->wsTable->get($key)['value']))) {
                        $swoole->push($swoole->wsTable->get($key)['value'], json_encode([
                            'getMaintenance' => [
                                'provider'          => $maintenance['provider'],
                                'under_maintenance' => $maintenance['under_maintenance'],
                            ]
                        ]));
                    }
                }
            }
        }

        return view('app', [
            'default_data' => $defaultData
        ]);
    }
}
