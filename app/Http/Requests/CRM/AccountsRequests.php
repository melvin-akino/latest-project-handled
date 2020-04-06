<?php

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AccountsRequests extends FormRequest{
    public function authorize(){
        return Auth::guard('crm')->check();
    }

    public function rules(){

        switch($this->method())
        {
            case 'POST':
            {
                return [
                    "first_name" => "max:100",
                    "last_name" => "max:100",
//          "status_id" => "required",
                    "username" => "required|min:4|unique:bit.users",
                    "password" => "required|min:4",
                    // "password" => ["required", "min:6", function ($attribute, $value, $fail) {

                    //     if(!preg_match('/[A-Z]/', $value)){
                    //         $fail($attribute . ' must contain 1 uppercase, 1 lowercase, 1 special character.');
                    //     }

                    //     if(!preg_match('/[a-z]/', $value)){
                    //         $fail($attribute . ' must contain 1 uppercase, 1 lowercase, 1 special character.');
                    //     }

                    //     if(!preg_match('/[0-9]/', $value)){
                    //         $fail($attribute . ' must contain 1 uppercase, 1 lowercase, 1 special character.');
                    //     }

                    //     if(!preg_match('/[&@!#+]/', $value)){
                    //         $fail($attribute . ' must contain 1 uppercase, 1 lowercase, 1 special character.');
                    //     }

                    // }
                    // ],
                    "currency_id" => "required"
                ];
            }
            case 'PUT':
            {
                $user      = $this->route('account');
                $user_id   = $user->id;
                return [
                    "firstname" => "required|max:32",
                    "lastname"  => "required|max:100",
                    "password"  => "nullable|min:4",
                    "state"     => "required",
                    "city"      => "required",
                    "postcode"  => "required",
                    "address"   => "required",
                    "phone"     => "required",
                    "email"     => "required|max:50|unique:users,email,$user_id,id",
                ];
            }
            default:break;
        }




    }
}
