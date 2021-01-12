<?php

namespace App\Http\Middleware;

use App\Facades\SwooleHandler;
use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StillActive
{
    public function handle($request, Closure $next)
    {
        Log::debug($request->path());
        Log::debug(Auth::user());

        $userStatus = Auth::user()->status;
        $cachedStatus = SwooleHandler::getValue('userStatusesTable', auth()->user()->id)['status'];
        if ($userStatus != 1 || $cachedStatus != 1) {
            if ($cachedStatus != $userStatus) {
                SwooleHandler::remove('userStatusesTable', auth()->user()->id);
                if ($userStatus != 1) {
                    Auth::user()->accessTokens()->update(['revoked' => true]);
                }

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
