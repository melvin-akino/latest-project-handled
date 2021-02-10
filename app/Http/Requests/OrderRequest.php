<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class OrderRequest extends FormRequest
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
        $path = $this->path();
        $rules = [];

        if (!empty($this->input('created_from'))) {
            $rules['created_from'] = 'required|date';
            $rules['created_to'] = 'required|date|after_or_equal:created_from';
        }

        if (!empty($this->input('settled_from'))) {
            $rules['settled_from'] = 'required|date';
            $rules['settled_to'] = 'required|date|after_or_equal:settled_from';
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status'      => false,
            'status_code' => 400,
            'errors'      => $validator->errors(),
        ], 400);

        throw new ValidationException($validator, $response);
    }
}
