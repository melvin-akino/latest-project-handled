<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\ErrorMessageRequest;
use App\Models\ErrorMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ErrorMessagesController extends Controller
{

    public function index() 
    {
        $data = [
            'page_title'       => "Error Messages",
            'page_description' => "Lists all error messages on betting",
            'dashboard_menu'   => true,
        ];
        return view('CRM.error_messages.index')->with($data);
    }

    public function list()
    {
        $errors = ErrorMessage::all();
        foreach ($errors as $error) {
            $data['data'][] = [
                'id'                => $error['id'],
                'error'              => $error['error'],
            ];
        }
        return response()->json(!empty($data) ? $data : []);
    }

    public function manage(ErrorMessageRequest $request) 
    {
        try {
            if (!empty($request)) {
                DB::beginTransaction();
                $data = $request->toArray();

                if (!empty($data['errorMessageId'])) {
                    $error = ErrorMessage::where('id', $data['errorMessageId'])->first();
                    $error->id = $data['errorMessageId'];
                    !empty($data['error']) ? $error->error = $data['error'] : null;               

                    if ($error->update($data)) {
                        $message = 'success';
                    }
                }
                else {

                    if (ErrorMessage::create($data)) {
                        $message = 'success';
                    }                    
                }

                DB::commit();

                return response()->json([
                    'status'      => true,
                    'status_code' => 200,
                    'data'        => $message
                ], 200);
            }            
        }  
        catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'errors'     => $e->getMessage()
            ], 500);
        }
    }


}
