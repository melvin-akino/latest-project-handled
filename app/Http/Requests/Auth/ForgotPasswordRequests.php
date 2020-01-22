<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ForgotPasswordRequests extends FormRequest
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
            'email'            => 'required|email|exists:users,email',
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
            'email.required'   => trans('validation.custom.email.required'),
            'email.email'      => trans('validation.custom.email.valid'),
            'email.exists'     => trans('validation.custom.email.exists'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status'           => false,
            'status_code'      => 422,
            'message'          => trans('validation.custom.error'),
            'errors'           => $validator->errors(),
        ], 422);

        throw new ValidationException($validator, $response);
    }
}