<?php

namespace App\Http\Requests\CRM;

use App\Models\ErrorMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ErrorMessageRequest extends FormRequest
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
        
        $existingErrorMessage = ErrorMessage::where('id', $this->input('errorMessageId'))->first();

        $update = !empty($existingErrorMessage->id) ? ",$existingErrorMessage->id" : ''; 
        
        $uniqueName = "|unique:error_messages,error$update";

        return [
            'error'   => "required|min:2|max:255$uniqueName",
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