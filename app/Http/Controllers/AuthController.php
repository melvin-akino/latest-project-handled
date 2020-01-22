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
     * @param  [integer]    country
     * @param  [integer]    state
     * @param  [integer]    city
     * @param  [string]     postcode
     * @param  [string]     phone
     * @param  [integer]    phone_country_code
     * @param  [integer]    odds_type
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
            'country'               => $request->country,
            'state'                 => $request->state,
            'city'                  => $request->city,
            'postcode'              => $request->postcode,
            'phone'                 => $request->phone,
            'phone_country_code'    => $request->phone_country_code,
            'odds_type'             => $request->odds_type,
            'currency_id'           => $request->currency_id,
            'birthdate'             => $request->birthdate
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

            $user = $request->user();
            $tokenResult = $user->createToken('Personal Access Token');
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
            ]);
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
        ]);
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
        ]);
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
        ]);
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

        $user->password = bcrypt($request->password);
        $user->save();

        $passwordReset->delete();

        $user->notify(new PasswordResetSuccess($passwordReset));

        return response()->json([
            'status'            => true,
            'status_code'       => 404,
            'data'              => $user,
            'message'           => trans('auth.password_reset.success'),
        ]);
    }
}
