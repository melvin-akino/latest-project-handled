<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequests extends FormRequest {
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
		// EXISTS Ruling
		// 'exists:<table>,<column_name>'

		return [
			'email' 				=> 'required|min:6|max:32', // Additional validation: `email` must exist from `users` table
			'password' 				=> 'required|min:6|max:32',
			'remember_me' 			=> 'boolean',
		];
	}

	/**
	 * Assign custom return responses for every request validation.
	 *
	 * @return array
	 */
	public function messages() {
		return [
			// USERNAME Input
			'email.required' 	=> "Username is required",
			'email.exists' 		=> "Username does not exist",

			// PASSWORD Input
			'password.required' 	=> "Password is required",
			'password.min' 			=> "Password must be at least 6 characters",
			'password.max' 			=> "Password is only up to 32 characters",

			// REMEMBER ME Input
			'remember_me.boolean' 	=> "Invalid input",
		];
	}

	public function response(array $error){
		return compact('error');
	}
}