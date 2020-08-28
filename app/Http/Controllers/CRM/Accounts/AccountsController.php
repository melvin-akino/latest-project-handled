<?php

namespace App\Http\Controllers\CRM\Accounts;

use App\Models\{Currency, Country, Source, UserWallet};
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

    public function postAddUser(Request $request)
    {
        try {
            $user = new User([
                'name'                  => explode('@', $request->email)[0],
                'email'                 => $request->email,
                'password'              => bcrypt($request->password),
                'firstname'             => $request->first_name,
                'lastname'              => $request->last_name,
                'country_id'            => 174,
                'currency_id'           => $request->currency_id,
                'status'                => 1
            ]);

            $user->save();

            $sourceId = Source::getIdByName('REGISTRATION');

            UserWallet::makeTransaction($user->id, 0, $request->currency_id, $sourceId, 'Credit');

            return response()->json([
                'status'                => true,
                'status_code'           => 200,
                'message'               => trans('auth.register.success'),
            ], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    public function update(AccountsRequests $request, User $account)
    {
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
