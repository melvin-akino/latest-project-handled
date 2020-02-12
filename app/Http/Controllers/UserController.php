<?php

namespace App\Http\Controllers;

use App\Models\{Provider, SportOddType, UserConfiguration, UserSportOddConfiguration};

use Illuminate\Http\Request;
use Swoole\Http\Request as SwooleRequest;
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
    public function user(Request $request, SwooleRequest $sRequest)
    {
//        app('swoole')->push($sRequest->fd, "sdfsdf");
        return response()->json(
            [
                'status'            => true,
                'status_code'       => 200,
                'data'              => $request->user()->only([
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
    }
}
