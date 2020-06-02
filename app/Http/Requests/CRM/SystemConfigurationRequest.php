<?php

namespace App\Http\Requests\CRM;

use App\Models\SystemConfiguration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SystemConfigurationRequest extends FormRequest
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
        return [
            'id'    => 'required',
            'type'  => 'required',
            'value' => 'required'
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