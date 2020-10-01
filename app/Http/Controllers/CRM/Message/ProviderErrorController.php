<?php

namespace App\Http\Controllers\CRM\Message;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

    public function create(Request $request)
    {
    	$data = [
				'message' 			=> $request->message,
				'error_message_id'  => $request->error_id
    		];
    		ProviderErrors::create($data);

    }
    public function update() 
    {

    }
    public function delete() 
    {

    }
}
