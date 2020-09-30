<?php

namespace App\Http\Requests\CRM;

use App\Models\Sport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SportRequest extends FormRequest
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
        
        $existingSport = Sport::where('id', $this->input('sportId'))->first();

        $update = !empty($existingSport->id) ? ",$existingSport->id" : ''; 
        
        $uniqueName = "|unique:sports,sport$update";

        return [
            'sport'   => "required|min:2|max:50$uniqueName",
            'details' => "required"
        ];
    }

    public function response(array $errors)
    {
        return response()->json([
            config('response.status') => config('response.type.error'),
            config('response.errors') => $errors
        ]);
    }
}