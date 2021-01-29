<?php

namespace App\Http\Controllers;

use App\Auth\PasswordReset;
use App\Facades\SwooleHandler;
use App\Http\Requests\Auth\{ChangePasswordRequests, ForgotPasswordRequests, LoginRequests, RegistrationRequests};
use App\Models\{
    Source,
    UserWallet
};
use App\Notifications\{PasswordResetRequest, PasswordResetSuccess, RegistrationMail};
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\{Str, Facades\Auth, Facades\Log};

use Exception;

class AuthController extends Controller
{
    /**
     * User Registration
     *
     * @param  [string]     name
     * @param  [string]     email
     * @param  [string]     password
     * @param  [string]     password_confirmation
     * @param  [string]     firstname
     * @param  [string]     lastname
     * @param  [string]     address
     * @param  [integer]    country_id
     * @param  [string]     state
     * @param  [string]     city
     * @param  [string]     postcode
     * @param  [string]     phone
     * @param  [integer]    currency_id
     * @param  [date]       birthdate
     *
     * @return [boolean]    status
     * @return [integer]    status_code
     * @return [string]     message
     */
//    public function register(RegistrationRequests $request)
//    {
//        try {
//            $user = new User([
//                'name'                  => $request->name,
//                'email'                 => $request->email,
//                'password'              => bcrypt($request->password),
//                'firstname'             => $request->firstname,
//                'lastname'              => $request->lastname,
//                'address'               => $request->address,
//                'country_id'            => $request->country_id,
//                'state'                 => $request->state,
//                'city'                  => $request->city,
//                'postcode'              => $request->postcode,
//                'phone'                 => $request->phone,
//                'currency_id'           => $request->currency_id,
//                'birthdate'             => $request->birthdate,
//                'status'                => 1
//            ]);
//
//            $user->save();
//
//            $sourceId = Source::getIdByName('REGISTRATION');
//
//            UserWallet::makeTransaction($user->id, 0, $request->currency_id, $sourceId, 'Credit');
//
//            $user->notify(
//                new RegistrationMail($request->name)
//            );
//
//            return response()->json([
//                'status'                => true,
//                'status_code'           => 200,
//                'message'               => trans('auth.register.success'),
//            ], 200);
//        } catch (Exception $e) {
//            Log::error($e->getMessage());
//            return response()->json([
//                'status'      => false,
//                'status_code' => 500,
//                'message'     => trans('generic.internal-server-error')
//            ], 500);
//        }
//    }

    /**
     * User Login
     *
     * @param  [string]     email
     * @param  [string]     password
     * @param  [boolean]    remember_me
     *
     * @return [boolean]    status
     * @return [integer]    status_code
     * @return [string]     access_token
     * @return [datetime]   expires_at
     * @return [string]     message
     * @return [string]     token_type
     */
    public function login(LoginRequests $request)
    {
        $server   = app('swoole');
        $wsTable  = $server->wsTable;

        try {
            $credentials = request(['email', 'password']);

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'status'      => false,
                    'status_code' => 401,
                    'message'     => trans('auth.login.401'),
                ], 401);
            }

            if (auth()->user()->status == 0) {
                return response()->json([
                    'status'      => false,
                    'status_code' => 401,
                    'message'     => trans('auth.login.suspended')
                ], 401);
            }

            if ($fd = $wsTable->get("uid:" . auth()->user()->id, 'value')) {
                if ($server->isEstablished($fd)) {
                    $server->push($fd, json_encode([
                        'userLogout' => true
                    ]));
                }
            }

            $user        = $request->user();
            $tokenResult = $user->createToken(env('PASSPORT_TOKEN', 'Multiline Authentication Token'));
            $token       = $tokenResult->token;

            if ($request->remember_me) {
                $token->expires_at = Carbon::now()->addWeeks(1);
            }

            $token->save();
            SwooleHandler::setValue('userStatusesTable', auth()->user()->id, ['status' => auth()->user()->status]);

            return response()->json([
                'status'       => true,
                'status_code'  => 200,
                'access_token' => $tokenResult->accessToken,
                'expires_at'   => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString(),
                'message'      => trans('auth.login.success'),
                'token_type'   => 'Bearer',
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "AuthController",
                "message"     => trans('generic.internal-server-error'),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error'),
            ], 500);
        }
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [boolean]    status
     * @return [integer]    status_code
     * @return [string]     message
     */
    public function logout(Request $request)
    {
        try {
            SwooleHandler::remove('userStatusesTable', Auth::user()->id);
            $request->user()->token()->revoke();
            deleteCookie('access_token');

            return response()->json([
                'status'        => true,
                'status_code'   => 200,
                'message'       => trans('auth.logout.success'),
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "AuthController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);
        }
    }

    /**
     * Create token password reset
     *
     * @param  [string]     email
     *
     * @return [boolean]    status
     * @return [integer]    status_code
     * @return [string]     message
     */
    public function create(ForgotPasswordRequests $request)
    {
        try {
            $user = User::where('email', $request->email)
                ->first();

            if (!$user) {
                $toLogs = [
                    "class"       => "AuthController",
                    "message"     => trans('auth.password_reset.email.404'),
                    "module"      => "API_ERROR",
                    "status_code" => 404,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'        => false,
                    'status_code'   => 404,
                    'message'       => trans('auth.password_reset.email.404'),
                ], 404);
            }

            if ($user->status == 0) {
                $toLogs = [
                    "class"       => "AuthController",
                    "message"     => trans('auth.login.451'),
                    "module"      => "API_ERROR",
                    "status_code" => 451,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'      => false,
                    'status_code' => 451,
                    'message'     => trans('auth.login.451')
                ], 451);
            }

            $passwordReset = PasswordReset::updateOrCreate(
                ['email' => $user->email],
                [
                    'email' => $user->email,
                    'token' => Str::random(60),
                ]
            );

            if ($user && $passwordReset) {
                $user->notify(
                    new PasswordResetRequest($passwordReset->token, $user->email)
                );
            }

            $toLogs = [
                "class"       => "AuthController",
                "message"     => trans('auth.password_reset.email.sent'),
                "module"      => "API",
                "status_code" => 200,
            ];
            monitorLog('monitor_api', 'info', $toLogs);

            return response()->json([
                'status'            => true,
                'status_code'       => 200,
                'message'           => trans('auth.password_reset.email.sent'),
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "AuthController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);
        }
    }

    /**
     * Find Password Reset Token
     *
     * @param  [string]     $token
     *
     * @return [boolean]    status
     * @return [integer]    status_code
     * @return [string]     message
     * @return [json]       passwordReset object
     */
    public function find($token)
    {
        try {
            $passwordReset = PasswordReset::where('token', $token)
                ->first();

            if (!$passwordReset) {
                $toLogs = [
                    "class"       => "AuthController",
                    "message"     => trans('auth.password_reset.token.404'),
                    "module"      => "API_ERROR",
                    "status_code" => 404,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'        => false,
                    'status_code'   => 404,
                    'message'       => trans('auth.password_reset.token.404'),
                ], 404);
            }

            if (User::where('email', $passwordReset->email)->first()->status == 0) {
                $toLogs = [
                    "class"       => "AuthController",
                    "message"     => trans('auth.login.451'),
                    "module"      => "API_ERROR",
                    "status_code" => 451,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'      => false,
                    'status_code' => 451,
                    'message'     => trans('auth.login.451')
                ], 451);
            }

            if (Carbon::parse($passwordReset->updated_at)->addMinutes(30)->isPast()) {
                $passwordReset->delete();

                $toLogs = [
                    "class"       => "AuthController",
                    "message"     => trans('auth.password_reset.token.404'),
                    "module"      => "API_ERROR",
                    "status_code" => 404,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'        => false,
                    'status_code'   => 404,
                    'message'       => trans('auth.password_reset.token.404'),
                ], 404);
            }

            return response()->json([
                'status'            => true,
                'status_code'       => 200,
                'message'           => $passwordReset,
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "AuthController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);
        }
    }

    /**
     * Reset Password
     *
     * @param  [string]     email
     * @param  [string]     password
     * @param  [string]     password_confirmation
     * @param  [string]     token
     *
     * @return [boolean]    status
     * @return [integer]    status_code
     * @return [string]     message
     * @return [json]       user object
     */
    public function reset(ChangePasswordRequests $request)
    {
        try {
            $passwordReset = PasswordReset::where([
                ['email', $request->email],
                ['token', $request->token],
            ])->first();

            if (!$passwordReset) {
                $toLogs = [
                    "class"       => "AuthController",
                    "message"     => trans('auth.password_reset.token.404'),
                    "module"      => "API_ERROR",
                    "status_code" => 404,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'        => false,
                    'status_code'   => 404,
                    'message'       => trans('auth.password_reset.token.404'),
                ], 404);
            }

            $user = User::where('email', $passwordReset->email)
                ->first();

            if (!$user) {
                $toLogs = [
                    "class"       => "AuthController",
                    "message"     => trans('auth.password_reset.email.404'),
                    "module"      => "API_ERROR",
                    "status_code" => 404,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'        => false,
                    'status_code'   => 404,
                    'message'       => trans('auth.password_reset.email.404'),
                ], 404);
            }

            if ($user->status == 0) {
                $toLogs = [
                    "class"       => "AuthController",
                    "message"     => trans('auth.login.451'),
                    "module"      => "API_ERROR",
                    "status_code" => 451,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'      => false,
                    'status_code' => 451,
                    'message'     => trans('auth.login.451')
                ], 451);
            }

            $user->password = bcrypt($request->password);
            $user->save();

            $passwordReset->delete();

            $user->notify(new PasswordResetSuccess($user));

            $toLogs = [
                "class"       => "AuthController",
                "message"     => "Password Reset Successful",
                "module"      => "API",
                "status_code" => 200,
            ];
            monitorLog('monitor_api', 'info', $toLogs);

            return response()->json([
                'status'            => true,
                'status_code'       => 200,
                'message'           => trans('auth.password_reset.success'),
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "AuthController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);
        }
    }
}
