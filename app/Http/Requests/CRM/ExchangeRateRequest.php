<?php

namespace App\Http\Requests\CRM;

use App\Models\ExchangeRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ExchangeRateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::guard('crm')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $existing_exchange_rate = ExchangeRate::where('from_currency_id', $this->input('from_currency'))
            ->where('to_currency_id', $this->input('to_currency'))
            ->first();

        switch ($this->method()) {
            case 'POST': {
                $unique = is_null($existing_exchange_rate) ? '' : '|unique:exchange_rates,from_currency_id';
             
                return [
                    'from_currency' => "required|$unique",
                    'to_currency'   => "required|different:from_currency|$unique",                   
                    'exchange_rate' => 'required|regex:/^\d*(\.\d{1,12})?$/|numeric|min:0.000000000001'
                ];
            }
            case 'PUT': {
                $exchange_rate = $this->route('exchange_rate');
                $existing_exchange_rate = ExchangeRate::where('from_currency_id', $this->input('from_currency'))
                    ->where('to_currency_id', $this->input('to_currency'))
                    ->where('id','<>',$exchange_rate->id)
                    ->first();
                 $unique = is_null($existing_exchange_rate) ? '' : '|unique:exchange_rates,from_currency_id';   
                
                return [
                    'from_currency' => "required|$unique",
                    'to_currency'   => "required|different:from_currency",                   
                    'exchange_rate' => 'required|regex:/^\d*(\.\d{1,12})?$/|numeric|min:0.000000000001'
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