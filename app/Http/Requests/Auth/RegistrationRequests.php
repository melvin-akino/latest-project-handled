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
        // EXISTS Ruling
        // 'exists:<table>,<column_name>'

        return [
            'firstname'                    => 'required|string',
            'lastname'                     => 'required|string',
            'name'                         => 'required|string|unique:users,name|min:6|max:32',
            'email'                        => 'required|email|unique:users,email',
            'password'                     => 'required|confirmed|min:6|max:32',
            'password_confirmation'        => 'required|same:password|min:6|max:32',
            'postcode'                     => 'required|numeric',
            'phone_country_code'           => 'required|numeric', // Additional validation: `phone_country_code`  must exist from `phone_country_code`  table
            'country'                      => 'required|numeric', // Additional validation: `country`             must exist from `country`             table
            'state'                        => 'required|numeric', // Additional validation: `state`               must exist from `state`               table
            'city'                         => 'required|numeric', // Additional validation: `city`                must exist from `city`                table
            'currency_id'                  => 'required|numeric', // Additional validation: `currency_id`         must exist from `currency`            table
            'odds_type'                    => 'required|numeric', // Additional validation: `odds_type`           must exist from `odds_type`           table
            'address'                      => 'required',
            'phone'                        => 'required',
            'birthdate'                    => 'date|nullable',
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
            'required'                     => trans('validation.custom.required'),
            'string'                       => trans('validation.custom.string'),
            'numeric'                      => trans('validation.custom.numeric'),
            'date'                         => trans('validation.custom.date'),
            'email'                        => trans('validation.custom.email.valid'),
            'exists'                       => trans('validation.custom.exists'),

            // UNIQUES
            'email.unique'                 => trans('validation.custom.email.unique'),
            'name.unique'                  => trans('validation.custom.name.unique'),

            // SAME
            'password_confirmation.same'   => trans('validation.custom.password_confirmation.same'),

            // MIN
            'name.min'                     => trans('validation.custom.name.min', ['count' => 6]),
            'password.min'                 => trans('validation.custom.password.min', ['count' => 6]),
            'password_confirmation.min'    => trans('validation.custom.password_confirmation.min', ['count' => 6]),

            // MAX
            'name.max'                     => trans('validation.custom.name.max', ['count' => 32]),
            'password.max'                 => trans('validation.custom.password.max', ['count' => 32]),
            'password_confirmation.max'    => trans('validation.custom.password_confirmation.max', ['count' => 32]),
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