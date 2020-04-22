<?php

namespace App\Http\Requests\CRM;

use App\Models\ProviderAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProviderAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $accounts = ProviderAccount::withTrashed()->where('username', $this->input('username'))->where('provider_id', $this->input('provider_id'))->get();
        $uniqueUsername = "";
        if (!empty($accounts)) {
            foreach($accounts as $account) {
                if ($account->id == $this->input('providerAccountId')){
                    $uniqueUsername = "|unique:provider_accounts,username,$account->id";
                    break;
                }
                elseif (is_null($account->deleted_at) && empty($this->input('providerAccountId'))) {
                    $uniqueUsername = "|unique:provider_accounts,username";
                    break;
                }
            }    
        }      
        
        return [
            'username'   => 'required|max:50'.$uniqueUsername,
            'password' => 'required',
            'pa_percentage'   => 'required|numeric'
        ];
    }

    public function response(array $errors)
    {
        return response()->json([
            config('response.status') => config('response.type.error'),
            config('response.errors') => $errors
        ]);
    }
}