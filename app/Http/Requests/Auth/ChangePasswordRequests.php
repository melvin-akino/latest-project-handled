<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ChangePasswordRequests extends FormRequest
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
            'email'                         => 'required|email|exists:users,email',
            'token'                         => 'required',
            'password'                      => 'required|confirmed|min:6|max:32|alpha_num',
            'password_confirmation'         => 'required|same:password|min:6|max:32|alpha_num',
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
            'required'                      => trans('validation.custom.required'),
            'email'                         => trans('validation.custom.email.valid'),

            'email.exists'                  => trans('validation.custom.email.exists'),

            'password.min'                  => trans('validation.custom.password.min', ['count' => 6]),
            'password.max'                  => trans('validation.custom.password.max', ['count' => 32]),

            'password_confirmation.min'     => trans('validation.custom.password_confirmation.min', ['count' => 6]),
            'password_confirmation.max'     => trans('validation.custom.password_confirmation.max', ['count' => 32]),
            'password_confirmation.same'    => trans('validation.custom.password_confirmation.same'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status'                        => false,
            'status_code'                   => 422,
            'message'                       => trans('validation.custom.error'),
            'errors'                        => $validator->errors(),
        ], 422);

        throw new ValidationException($validator, $response);
    }
}
