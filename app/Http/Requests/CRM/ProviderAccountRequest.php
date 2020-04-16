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
        $existingProviderAccount = ProviderAccount::where('id', $this->input('providerAccountId'))->first();

        $update = !empty($existingProviderAccount->id) ? ",$existingProviderAccount->id" : ''; 
        
        $uniqueUsername = "|unique:provider_accounts,username$update";
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