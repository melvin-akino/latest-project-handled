<?php

namespace App\Http\Controllers;

use App\User;
use App\Auth\PasswordReset;

use App\Http\Requests\Auth\LoginRequests;
use App\Http\Requests\Auth\RegistrationRequests;
use App\Http\Requests\Auth\ForgotPasswordRequests;
use App\Http\Requests\Auth\ChangePasswordRequests;

use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
     * @param  [integer]    phone_country_code
     * @param  [integer]    currency_id
     * @param  [date]       birthdate
     *
     * @return [boolean]    status
     * @return [integer]    status_code
     * @return [string]     message
     */
    public function register(RegistrationRequests $request)
    {
        $user = new User([
            'name'                  => $request->name,
            'email'                 => $request->email,
            'password'              => bcrypt($request->password),
            'firstname'             => $request->firstname,
            'lastname'              => $request->lastname,
            'address'               => $request->address,
            'country_id'            => $request->country_id,
            'state'                 => $request->state,
            'city'                  => $request->city,
            'postcode'              => $request->postcode,
            'phone'                 => $request->phone,
            'phone_country_code'    => $request->phone_country_code,
            'currency_id'           => $request->currency_id,
            'birthdate'             => $request->birthdate,
            'status'                => 1
        ]);

        $user->save();

        return response()->json([
            'status'                => true,
            'status_code'           => 200,
            'message'               => trans('auth.register.success'),
        ], 200);
    }

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
        try {
            $credentials = request(['email', 'password']);

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'status'      => false,
                    'status_code' => 401,
                    'message'     => trans('auth.login.401'),
                ], 401);
            }

            if (User::activeUser(auth()->user()->id)->count() == 0) {
                return response()->json([
                    'status'      => false,
                    'status_code' => 451,
                    'message'     => trans('auth.login.451')
                ], 451);
            }

            $user = $request->user();
            $tokenResult = $user->createToken(env('PASSPORT_TOKEN', 'Multiline Authentication Token'));
            $token = $tokenResult->token;

            if ($request->remember_me) {
                $token->expires_at = Carbon::now()->addWeeks(1);
            }

            $token->save();

            return response()->json([
                'status'       => true,
                'status_code'  => 200,
                'access_token' => $tokenResult->accessToken,
                'expires_at'   => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString(),
                'message'      => trans('auth.login.success'),
                'token_type'   => 'Bearer',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 200);
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
        $request->user()->token()->revoke();

        return response()->json([
            'status'        => true,
            'status_code'   => 200,
            'message'       => trans('auth.logout.success'),
        ], 200);
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
        $user = User::where('email', $request->email)
            ->first();

        if (!$user) {
            return response()->json([
                'status'        => false,
                'status_code'   => 404,
                'message'       => trans('auth.password_reset.email.404'),
            ], 404);
        }

        if ($user->status == 0) {
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

        return response()->json([
            'status'            => true,
            'status_code'       => 200,
            'message'           => trans('auth.password_reset.email.sent'),
        ], 200);
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
        $passwordReset = PasswordReset::where('token', $token)
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'status'        => false,
                'status_code'   => 404,
                'message'       => trans('auth.password_reset.token.404'),
            ], 404);
        }

        if (User::where('email', $passwordReset->email)->first()->status == 0) {
            return response()->json([
                'status'      => false,
                'status_code' => 451,
                'message'     => trans('auth.login.451')
            ], 451);
        }

        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();

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
        $passwordReset = PasswordReset::where([
            ['email', $request->email],
            ['token', $request->token],
        ])->first();

        if (!$passwordReset) {
            return response()->json([
                'status'        => false,
                'status_code'   => 404,
                'message'       => trans('auth.password_reset.token.404'),
            ], 404);
        }

        $user = User::where('email', $passwordReset->email)
            ->first();

        if (!$user) {
            return response()->json([
                'status'        => false,
                'status_code'   => 404,
                'message'       => trans('auth.password_reset.email.404'),
            ], 404);
        }

        if ($user->status == 0) {
            return response()->json([
                'status'      => false,
                'status_code' => 451,
                'message'     => trans('auth.login.451')
            ], 451);
        }

        $user->password = bcrypt($request->password);
        $user->save();

        $passwordReset->delete();

        $user->notify(new PasswordResetSuccess($passwordReset));

        return response()->json([
            'status'            => true,
            'status_code'       => 200,
            'data'              => $user,
            'message'           => trans('auth.password_reset.success'),
        ], 200);
    }
}
