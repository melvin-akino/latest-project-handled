<?php

namespace App\Http\Requests\CRM;

use App\Models\Provider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProviderRequest extends FormRequest
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
        
        $existingProvider = Provider::where('id', $this->input('providerId'))->first();

        $update = !empty($existingProvider->id) ? ",$existingProvider->id" : ''; 
        
        $uniqueName = "|unique:providers,name$update";
        $uniqueAlias= "|unique:providers,alias$update";

        return [
            'name'   => "required|min:2|max:50$uniqueName",
            'alias' => "required|max:5$uniqueAlias",
            'percentage'   => 'required|numeric'
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