<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SettingsRequests extends FormRequest
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
        $type = $this->route('type');

        return [
            //
        ];
    }

    /**
     * Assign custom return responses for every request validation.
     *
     * @return array
     */
    public function messages()
    {
        return [
            //
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status'                       => false,
            'status_code'                  => 422,
            'message'                      => trans('validation.custom.error'),
            'errors'                       => $validator->errors(),
        ], 422);

        throw new ValidationException($validator, $response);
    }
}
