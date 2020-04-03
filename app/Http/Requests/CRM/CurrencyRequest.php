<?php

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CurrencyRequest extends FormRequest
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
        switch ($this->method()) {
            case 'POST': {

                return [
                    'currency_name'   => 'required|min:2|max:50|unique:currency,name',
                    'currency_symbol' => 'required|max:5|unique:currency,symbol',
                    'currency_code'   => 'required|max:5|unique:currency,code',
                ];
            }
            case 'PUT': {
                $currency = $this->route('currency');
                $currency_id = $currency->id;
                
                return [
                    'currency_name'   => "required|min:2|max:50|unique:currency,name,$currency_id,id",
                    'currency_symbol' => "required|max:5|unique:currency,symbol,$currency_id,id",
                    'currency_code'   => "required|max:5|unique:currency,code,$currency_id,id",

                ];
            }
        }
    }

    public function response(array $errors)
    {
        return response()->json([
            config('response.status') => config('response.type.error'),
            config('response.errors') => $errors
        ]);
    }
}
