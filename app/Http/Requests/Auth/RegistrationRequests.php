<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RegistrationRequests extends FormRequest
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
            'firstname'             => 'required|string|max:32',
            'lastname'              => 'required|string|max:32',
            'name'                  => 'required|string|regex:/^[a-zA-Z0-9]+$/|unique:users,name|min:6|max:32',
            'email'                 => 'required|email|max:100|unique:users,email',
            'password'              => 'required|confirmed|min:6|max:32|alpha_num',
            'password_confirmation' => 'required|same:password|min:6|max:32|alpha_num',
            'postcode'              => 'required|string|regex:/^[0-9]{3,6}$/|min:3|max:6',
            'country_id'            => 'required|numeric|exists:countries,id',
            'state'                 => 'required|max:100',
            'city'                  => 'required|max:100',
            'currency_id'           => 'required|numeric|exists:' . config('database.crm_default') . '.currency,id',
            'address'               => 'required',
            'phone'                 => 'required|string|regex:/^[0-9]{6,32}$/|min:6',
            'birthdate'             => 'date|nullable',
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
            // GLOBAL VALIDATION
            'required'                   => trans('validation.custom.required'),
            'string'                     => trans('validation.custom.string'),
            'numeric'                    => trans('validation.custom.numeric'),
            'date'                       => trans('validation.custom.date'),
            'email'                      => trans('validation.custom.email.valid'),
            'exists'                     => trans('validation.custom.exists'),

            // UNIQUES
            'email.unique'               => trans('validation.custom.email.unique'),
            'name.unique'                => trans('validation.custom.name.unique'),

            // REGEX
            'name.regex'                 => trans('validation.custom.name.alphanumeric'),

            // SAME
            'password_confirmation.same' => trans('validation.custom.password_confirmation.same'),

            // MIN
            'name.min'                   => trans('validation.custom.name.min', ['count' => 6]),
            'password.min'               => trans('validation.custom.password.min', ['count' => 6]),
            'password_confirmation.min'  => trans('validation.custom.password_confirmation.min', ['count' => 6]),

            // MAX
            'name.max'                   => trans('validation.custom.name.max', ['count' => 32]),
            'password.max'               => trans('validation.custom.password.max', ['count' => 32]),
            'password_confirmation.max'  => trans('validation.custom.password_confirmation.max', ['count' => 32]),

            'phone.min'                  => trans('validation.custom.phone.min'),
            'phone.regex'                => trans('validation.custom.phone.regex'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status'      => false,
            'status_code' => 422,
            'message'     => trans('validation.custom.error'),
            'errors'      => $validator->errors(),
        ], 422);

        throw new ValidationException($validator, $response);
    }
}
