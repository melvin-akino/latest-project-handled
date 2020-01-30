<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class LoginRequests extends FormRequest
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
            'email'                => 'required|email|min:6|max:32',
            'password'             => 'required|min:6|max:32',
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
            // GLOBAL
            'email'                 => trans('validation.custom.email.valid'),
            'required'              => trans('validation.custom.required'),

            // PASSWORD Input
            'password.min'          => trans('validation.custom.password.min', ['count' => 6]),
            'password.max'          => trans('validation.custom.password.max', ['count' => 32]),

            // REMEMBER ME Input
            'remember_me.boolean'   => trans('validaiton.custom.remember_me.boolean'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status'                => false,
            'status_code'           => 422,
            'message'               => trans('validation.custom.error'),
            'errors'                => $validator->errors(),
        ], 422);

        throw new ValidationException($validator, $response);
    }
}