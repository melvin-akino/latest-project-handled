<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\ProviderRequest;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ProvidersController extends Controller
{

    public function index() 
    {
        $data = [
            'page_title'       => "Providers",
            'page_description' => "Lists all providers and accounts",
            'dashboard_menu'   => true,
        ];
        return view('CRM.providers.providers')->with($data);
    }

    public function list()
    {
        $providers = Provider::getAllProviders();
        foreach ($providers as $provider) {
            $data['data'][] = [
                'id'                => $provider['id'],
                'name'              => $provider['name'],
                'alias'             => $provider['alias'],
                'percentage'        => $provider['punter_percentage'],
                'priority'          => $provider['priority'],
                'is_enabled'        => $provider['is_enabled']
            ];
        }

        return response()->json(!empty($data) ? $data : []);
    }

    public function manage(ProviderRequest $request) 
    {
        try {
            
            if (!empty($request)) {
                DB::beginTransaction();
                $requestData = $request->all();
                !empty($requestData['providerId']) ? $data['id'] = $requestData['providerId'] : null;
                !empty($requestData['name']) ? $data['name'] = $requestData['name'] : null;
                !empty($requestData['alias']) ? $data['alias'] = $requestData['alias'] : null;
                !empty($requestData['percentage']) ? $data['punter_percentage'] = $requestData['percentage'] : 0;
                !empty($requestData['is_enabled']) ? $data['is_enabled'] = ($requestData['is_enabled'] == 'true') ? 1 : 0 : 0;
                
                if (!empty($data['id'])) {
                    $provider = Provider::where('id', $data['id'])->first();
                    $provider->id = $data['id'];
                    !empty($data['name']) ? $provider->name = $data['name'] : null;
                    !empty($data['alias']) ? $provider->alias = $data['alias'] : null;
                    !empty($data['percentage']) ? $provider->punter_percentage = $data['percentage'] : null;
                    !empty($data['is_enabled']) ? $provider->is_enabled = $data['is_enabled'] : null;
                    

                    if ($provider->update($data)) {
                        $message = 'success';
                    }
                }
                else {
                    //get the latest priority
                    $latest = Provider::getLatestPriority();
                    
                    $data['priority'] = $latest->priority + 1;

                    if (Provider::create($data)) {
                        $message = 'success';
                    }                    
                }

                DB::commit();

                return response()->json([
                    'status'      => true,
                    'status_code' => 200,
                    'data'        => $message
                ], 200);
            }            
        }  
        catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'errors'     => $e->getMessages()
            ], 500);
        }
    }

    public function prioritize(Request $request)
    {
        $providers = Provider::all();

        foreach ($providers as $provider) {
            foreach ($request->order as $order) {
                if ($order['id'] == $provider->id) {
                    $provider->update(['priority' => $order['position']]);
                }
            }
        }
        return response()->json([
            'status'      => true,
            'status_code' => 200,
            'data'        => 'success'
        ], 200);
    }

}
