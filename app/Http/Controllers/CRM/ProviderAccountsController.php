<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\ProviderAccount;
use App\Models\SystemConfiguration;
use Illuminate\Http\Request;

class ProviderAccountsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:crm');
    }

    public function index($id)
    {
        if (!session()->has('crm_user')) {
            return redirect()->route('crm');
        }

        $accounts = ProviderAccount::getProviderAccounts($id);
        $data = [];
        if (!empty($accounts)) {
            foreach($accounts as $account) {
                $data['data'][] = [
                    'id'            => $account['id'],
                    'username'      => $account['username'],
                    'password'      => $account['password'],
                    'type'          => $account['type'],
                    'percentage'    => $account['punter_percentage'],
                    'credits'       => $account['credits'],
                    'is_enabled'    => $account['is_enabled'],
                    'is_idle'       => $account['is_idle']
                ];
            }
        }

        return response()->json($data);
    }

    public function manage(Request $request) {
        try {
            if (!empty($request)) {
                
                !empty($request->id) ? $data['id'] = $request->providerAccountId : null;
                !empty($request->username) ? $data['username'] = $request->username : null;
                !empty($request->password) ? $data['password'] = $request->password : null;
                !empty($request->provider_id) ? $data['provider_id'] = $request->provider_id : 0;
                !empty($request->account_type) ? $data['type'] = $request->account_type : null;
                !empty($request->pa_percentage) ? $data['punter_percentage'] = $request->pa_percentage : null;
                !empty($request->credits) ? $data['credits'] = $request->credits : 0;
                !empty($request->pa_is_enabled) ? $data['is_enabled'] = $request->pa_is_enabled : 0;
                !empty($request->is_idle) ? $data['is_idle'] = $request->is_idle : 0;

                //
                //dd($data);
                if (!empty($request->providerAccountId)) {
                    $providerAccount = ProviderAccount::where('id', $request->providerAccountId)->first();
                    if ($providerAccount->update($data)) {

                    $message = 'success';   
                    }
                    
                }
                else {
                    if (ProviderAccount::create($data)) {
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
