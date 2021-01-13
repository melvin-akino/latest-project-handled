<?php

namespace App\Http\Middleware;

use App\Facades\SwooleHandler;
use Closure;
use Illuminate\Support\Facades\Auth;

class StillActive
{
    public function handle($request, Closure $next)
    {
        $userStatus   = Auth::user()->status;
        $cachedStatus = SwooleHandler::getValue('userStatusesTable', auth()->user()->id)['status'];
        if ($userStatus != 1 || $cachedStatus != 1) {
            if ($cachedStatus != $userStatus) {
                SwooleHandler::remove('userStatusesTable', auth()->user()->id);
                $request->user()->token()->revoke();

                return response()->json([
                    'status'      => false,
                    'status_code' => 401,
                    'message'     => trans('generic.unauthorized')
                ], 401);
            }
        }
        return $next($request);
    }
}
