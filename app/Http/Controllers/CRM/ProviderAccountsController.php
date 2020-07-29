<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\ProviderAccountRequest;
use App\Models\{ProviderAccount, SystemConfiguration};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProviderAccountsController extends Controller
{
    public function index($id)
    {

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

    public function manage(ProviderAccountRequest $request) 
    {
        try 
        {
            if (!empty($request)) {
                DB::beginTransaction();                
                !empty($request->providerAccountId) ? $data['id'] = $request->providerAccountId : null;
                !empty($request->username) ? $data['username'] = $request->username : null;
                !empty($request->password) ? $data['password'] = $request->password : null;
                !empty($request->provider_id) ? $data['provider_id'] = $request->provider_id : 0;
                !empty($request->account_type) ? $data['type'] = $request->account_type : null;
                !empty($request->pa_percentage) ? $data['punter_percentage'] = $request->pa_percentage : 0;
                !empty($request->credits) ? $data['credits'] = $request->credits : 0;
                !empty($request->pa_is_enabled) ? $data['is_enabled'] = true : $data['is_enabled'] = false;
                !empty($request->is_idle) ? $data['is_idle'] = true : $data['is_idle'] = false;
                $data['updated_at'] = Carbon::now();

                //Record is on update process
                if (!empty($request->providerAccountId))  {
                    $providerAccount = ProviderAccount::where('id', $request->providerAccountId)->first();
                }
                else {
                    $providerAccount = ProviderAccount::withTrashed()->where('username', $request->username)
                        ->where('provider_id', $request->provider_id)->first();

                    if (!empty($providerAccount)) {
                        ProviderAccount::withTrashed()->where('username', $request->username)
                            ->where('provider_id', $request->provider_id)->first()->restore();
                    }    
                    
                }               

                if (!empty($providerAccount)) {

                    if ($providerAccount->update($data)) {

                        $message = 'success';   
                    }
                    
                }
                else
                {    
                    if (ProviderAccount::create($data)) {
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
        catch (Exception $e) 
        {
            DB::rollBack();
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'errors'     => $e->getMessages()
            ], 500);
        }
    }

    public function softDelete($id) 
    {
        try {

            $deleted = ProviderAccount::find($id)->delete();
            $message = 'failed';

            if ($deleted) {
                $message = 'success';                
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $message
            ], 200);
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
