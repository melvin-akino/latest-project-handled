<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\{MasterLeague, League};
class ToggleLeaguesRequest extends FormRequest
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
            'master_league_id' => 'required|exists:master_leagues,id',
            'schedule'         => 'required',
            'sport_id'         => 'required|exists:sports,id'
        ];
    }

    public function messages()
    {
        return [
            'required' => trans('validation.required'),
            'exists'   => trans('validation.custom.exists')
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
