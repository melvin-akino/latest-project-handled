<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequests extends FormRequest {
	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize() {
		return true;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules() {
		return [
			'email' 						=> 'required|email',
			'token' 						=> 'required',
			'password' 						=> 'required|confirmed|min:6|max:32',
			'password_confirmation' 		=> 'required|same:password|min:6|max:32',
		];
	}

	/**
	 * Assign custom return responses for every request validation.
	 *
	 * @return array
	 */
	public function messages() {
		return [
			'required' 						=> "This field is required",
			'email' 						=> "Please input a valid E-mail format",

			'password.min' 					=> "Password must be at least 6 characters",
			'password.max' 					=> "Password is only up to 32 characters",

			'password_confirmation.min' 	=> "Confirm Password must be at least 6 characters",
			'password_confirmation.max' 	=> "Confirm Password is only up to 32 characters",
			'password_confirmation.same' 	=> "Confirm Password must be the same with Password",
		];
	}

	public function response(array $error){
		return compact('error');
	}
}