<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

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
            'firstname'                      => 'required|string',
            'lastname'                       => 'required|string',
            'name'                           => 'required|string|unique:users,name|min:6|max:32',
            'email'                          => 'required|email|unique:users,email',
            'password'                       => 'required|confirmed|min:6|max:32',
            'password_confirmation'          => 'required|same:password|min:6|max:32',
            'postcode'                       => 'required|numeric',
            'phone_country_code'             => 'required|numeric', // Additional validation: `phone_country_code`  must exist from `phone_country_code`  table
            'country'                        => 'required|numeric', // Additional validation: `country`             must exist from `country`             table
            'state'                          => 'required|numeric', // Additional validation: `state`               must exist from `state`               table
            'city'                           => 'required|numeric', // Additional validation: `city`                must exist from `city`                table
            'currency_id'                    => 'required|numeric', // Additional validation: `currency_id`         must exist from `currency`            table
            'odds_type'                      => 'required|numeric', // Additional validation: `odds_type`           must exist from `odds_type`           table
            'address'                        => 'required',
            'phone'                          => 'required',
            'birthdate'                      => 'date|nullable',
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
            'required'                       => "This field is required",
            'string'                         => "Invalid input. Field must be a string",
            'numeric'                        => "Invalid input. Field must be a number",
            'date'                           => "Invalid input. Field must be a date format",
            'email'                          => "Please input a valid E-mail format",
            'exists'                         => "Invalid input. Entry does not exist from our records",

            // UNIQUES
            'email.unique'                   => "E-mail Address already exists",
            'name.unique'                    => "Username already exists",

            // SAME
            'password_confirmation.same'     => "Confirm Password must be the same with Password",

            // MIN
            'name.min'                       => "Username must be at least 6 characters",
            'password.min'                   => "Password must be at least 6 characters",
            'password_confirmation.min'      => "Confirm Password must be at least 6 characters",

            // MAX
            'name.max'                       => "Username is only up to 32 characters",
            'password.max'                   => "Password is only up to 32 characters",
            'password_confirmation.max'      => "Confirm Password is only up to 32 characters",
        ];
    }

    public function response(array $error)
    {
        return compact('error');
    }
}
