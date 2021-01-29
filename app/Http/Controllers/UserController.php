<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Exception;

class UserController extends Controller
{
    /**
     * Get the authenticated User
     *
     * @return [boolean]    status
     * @return [integer]    status_code
     * @return [json]       object
     */
    public function user(Request $request)
    {
        try {
            return response()->json(
                [
                    'status'      => true,
                    'status_code' => 200,
                    'data'        => $request->user()->only([
                        'name',
                        'email',
                        'firstname',
                        'lastname',
                        'phone',
                        'address',
                        'country_id',
                        'state',
                        'city',
                        'postcode',
                        'currency_id',
                        'birthdate',
                    ])
                ]
            );
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "UserController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }
}
