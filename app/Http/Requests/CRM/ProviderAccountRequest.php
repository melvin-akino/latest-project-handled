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
        $existingProviderAccounts = ProviderAccount::where('username', $this->input('username'))->get();
        $uniqueUsername = '';

        foreach ($existingProviderAccounts as $providerAccount) {
            //check if this retrieved username has the same provider_id as this value being validated
            if ($providerAccount->id == $this->input('providerAccountId')/*updating this record*/ && $providerAccount->provider_id == $this->input('provider_id')/*same provider*/) {
                $uniqueUsername = "|unique:provider_accounts,username,$providerAccount->id";
            }
            elseif (empty($this->input('providerAccountId'))/*new input*/ && $providerAccount->provider_id == $this->input('provider_id')/*same provider*/ && is_null($providerAccount->deleted_at)/*not deleted*/) {
                $uniqueUsername = "|unique:provider_accounts";
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