<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

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
            'email.required'   => "E-mail Address is required",
            'email.email'      => "Please input a valid E-mail format",
            'email.exists'     => "E-mail Address does not exist from our records",
        ];
    }

    public function response(array $error)
    {
        return compact('error');
    }
}