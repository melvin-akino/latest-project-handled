<?php

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AccountsRequests extends FormRequest
{
    public function authorize()
    {
        return Auth::guard('crm')->check();
    }

    public function rules()
    {

        switch($this->method())
        {
            
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
