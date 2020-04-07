<?php

namespace App\Http\Controllers\CRM\Accounts;

//use App\CRM\baccarat\Status;
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
        $data['page_title'] = "Accounts";
        $data['accounts_menu'] = true;
        $data['currencies'] = Currency::get();

        $data['page_description'] = 'Account Management';

        return view('CRM.accounts.index')->with($data);
    }

    public function dataTable(Request $request)
    {
        
        return dataTable($request, User::query());
    }

    public function update(AccountsRequests $request, User $account){
        $account->update($request->except(['password']));
        \Log::info(json_encode($request));
        \Log::info('accountrequest');
//      account->birthdate = $request->birth_date;

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
        $data['page_title'] = 'Account Information';
        $data['page_description'] = 'Account Management';
        $data['account'] = $account;
        
        $data['accounts_menu'] = true;
        $data['countries'] = Country::get();
        $data['currencies'] = Currency::get();
        return view('CRM.accounts.details.index')->with($data);
    }

    public function generateUsername()
    {
        $faker = \Faker\Factory::create();
        $gen_username = $faker->userName(9);

        while( (strlen($gen_username) < 9) || (User::where('username', $gen_username)->count() != 0)) {
            $gen_username = $faker->userName;
        }

        // remove dot and exactly 8 char
        $gen_username = substr(str_replace('.', '', $gen_username), 0, 8);

        return $gen_username;
    }

    public function addAccount(AccountsRequests $request)
    {

        $status_id = Status::where('status_type', 'Account')
            ->where('status_name', 'Active')
            ->first()
            ->status_id;

        $a = Accounts::create([
            //set empty for now
            'name' => '',
            'email' => $request->username.'@ninepinetech.com',

            'username' => $request->username,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'status_id' => $status_id,
            'currency_id' => $request->currency_id,
        ]);

        // i had problems with database with is_reset boolean
        $a->is_reset = true;
        $a->save();

        return redirect(route('accounts.index'));
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

        $account = User::where('id', $request->user_id)->get();
        $account->password = Hash::make($request->password);
        $account->is_reset = true;
        $account->save();

        return response()->json([
            'status' => 'success'
        ]);

    }
}
