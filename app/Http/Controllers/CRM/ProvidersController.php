<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\Request;

class ProvidersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:crm');
    }
    public function index() {
        if (!session()->has('crm_user')) {
            return redirect()->route('crm');
        }

        $data = [
            'page_title'       => "Providers",
            'page_description' => "Lists all provider accounts",
            'dashboard_menu'   => true,
        ];
        return view('CRM.providers.providers')->with($data);
    }

    public function list()
    {
        if (!session()->has('crm_user')) {
            return redirect()->route('crm');
        }

        $providers = Provider::getAllProviders();
        foreach ($providers as $provider) {
            $data['data'][] = [
                'id'                => $provider['id'],
                'name'              => $provider['name'],
                'alias'             => $provider['alias'],
                'percentage'        => $provider['punter_percentage'],
                'is_enabled'        => $provider['is_enabled']
            ];
        }

        return response()->json($data);
    }

    public function manage(Request $request) {
        try {
            
            if (!empty($request)) {

                $requestData = $request->all();
                !empty($requestData['providerId']) ? $data['id'] = $requestData['providerId'] : null;
                !empty($requestData['name']) ? $data['name'] = $requestData['name'] : null;
                !empty($requestData['alias']) ? $data['alias'] = $requestData['alias'] : null;
                !empty($requestData['percentage']) ? $data['punter_percentage'] = $requestData['percentage'] : null;
                !empty($requestData['is_enabled']) ? $data['is_enabled'] = ($requestData['is_enabled'] == 'true') ? 1 : 0 : 0;

                if (!empty($data['id'])) {
                    $provider = Provider::where('id', $data['id'])->first();
                    $provider->id = $data['id'];
                    !empty($data['name']) ? $provider->name = $data['name'] : null;
                    !empty($data['alias']) ? $provider->alias = $data['name'] : null;
                    !empty($data['percentage']) ? $provider->punter_percentage = $data['percentage'] : null;
                    !empty($data['is_enabled']) ? $provider->is_enabled = $data['is_enabled'] : null;
                    
                    if ($provider->update($data)) {
                        $message = 'success';
                    }
                }
                else {                  
                    if (Provider::create($data)) {
                        $message = 'success';
                    }                    
                }

                return response()->json([
                    'status'      => true,
                    'status_code' => 200,
                    'data'        => $message
                ], 200);
            }            
        }  
        catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

}
