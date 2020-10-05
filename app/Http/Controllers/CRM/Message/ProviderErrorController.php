<?php

namespace App\Http\Controllers\CRM\Message;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CRM\{ProviderErrorRequest, ProviderErrorEditRequest};
use App\Models\{ProviderErrors, ErrorMessage};

class ProviderErrorController extends Controller
{
    //

    public function index() 

    {

    	$data['page_title']       = "Provider Error Message";
        $data['accounts_menu']    = true;
        $data['errormessages']    = ErrorMessage::get();
        $data['page_description'] = 'List all error Messages';

        return view('CRM.error_messages.provider_error')->with($data);


    }
    public function dataTable(Request $request)
    {
        return dataTable($request, ProviderErrors::with('Errorvalue'));
    }

    public function create(ProviderErrorRequest $request)
    {
    	$data = [
				'message' 			=> $request->message,
				'error_message_id'  => $request->error_id
    		];
    	ProviderErrors::create($data);
    	return response()->json([
            config('response.status') => config('response.type.success')
        ], 201);

    }
    public function update(ProviderErrorEditRequest $request) 
    {

    	$data =[
    			'message'			=> $request->edit_message,
    			'error_message_id'	=> $request->error_id
    		];
    	ProviderErrors::find($request->edit_id)->update($data);

    	return response()->json([
            config('response.status') => config('response.type.success')
        ], 201);
    


    }

   
}
