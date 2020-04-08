<?php

namespace App\Http\Controllers\CRM\Accounts;

use App\Models\{Currency, Country};
use App\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\AccountsRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;


class AccountsController extends Controller
{
   
    public function index()
    {
        $data['page_title']       = "Accounts";
        $data['accounts_menu']    = true;
        $data['currencies']       = Currency::get();
        $data['page_description'] = 'Account Management';

        return view('CRM.accounts.index')->with($data);
    }

    public function dataTable(Request $request)
    {
        
        return dataTable($request, User::query());
    }

    public function update(AccountsRequests $request, User $account){
        $account->update($request->except(['password']));

        if($request->password){
            $account->password = Hash::make($request->password);
            $account->is_reset = true;
        }
        $account->save();

        $swal = trans('swal.account.update.success');

        return redirect()->back()->with(compact('swal'));
    }

    public function details(Request $request, User $account)
    {
        $data['page_title']       = 'Account Information';
        $data['page_description'] = 'Account Management';
        $data['account']          = $account;
        $data['accounts_menu']    = true;
        $data['countries']        = Country::get();
        $data['currencies']       = Currency::get();

        return view('CRM.accounts.details.index')->with($data);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            "password" => ["required", "min:6", "confirmed", function ($attribute, $value, $fail) {

                if(!preg_match('/[A-Z]/', $value)){
                    $fail($attribute . ' must contain 1 uppercase, 1 lowercase, 1 special character.');
                }

                if(!preg_match('/[a-z]/', $value)){
                    $fail($attribute . ' must contain 1 uppercase, 1 lowercase, 1 special character.');
                }

                if(!preg_match('/[0-9]/', $value)){
                    $fail($attribute . ' must contain 1 uppercase, 1 lowercase, 1 special character.');
                }

                if(!preg_match('/[&@!#+]/', $value)){
                    $fail($attribute . ' must contain 1 uppercase, 1 lowercase, 1 special character.');
                }

            }
            ]
        ]);

        $account           = User::where('id', $request->user_id)->get();
        $account->password = Hash::make($request->password);
        $account->is_reset = true;
        $account->save();

        return response()->json([
            'status' => 'success'
        ]);

    }
}
