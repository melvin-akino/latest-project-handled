<?php

namespace App\Http\Controllers\CRM\Auth;

use App\Http\Controllers\Controller;
use App\CRM\NinepineModels\Status;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Auth;

class LoginController extends Controller{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin/dashboard';
    protected $guard      = 'crm';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(){
        $prev = url()->previous();

        session([
            'url.intended' => $prev == config('app.url')
                ? $this->redirectTo
                : $prev
        ]);

        $this->redirectTo = session()->get('url.intended');
        $this->middleware('guest')->except('logout');
    }

    public function index()
    {
        return view('CRM.auth.login');
    }

    public function username()
    {
        return 'email';
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required|min:6'
        ]);

        if (Auth::guard('crm')->attempt(['email' => $request->email, 'password' => $request->password])) {
            Session::put('crm_user', true);

            return redirect('/admin/dashboard');
        } else {
            return $this->sendFailedLoginResponse($request);
        }
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    protected function credentials(\Illuminate\Http\Request $request)
    {
        // $active_status_id = Status::where('status_type', 'User')
        //     ->where('status_name', 'Active')->first();

        return [
            'email'     => $request->{$this->username()},
            'password'  => $request->password,
            // 'status_id' => $active_status_id->status_id
            'status_id' => 1
        ];
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        session(['url.intended' => ""]);

        Session::forget('url.intended');
        Session::forget('crm_user');
        Session::forget('user');

        return redirect()->route('crm');
    }

    public function showLoginForm()
    {
        return !Auth::guard('crm')->check()
            ? view('CRM.auth.login')
            : redirect('admin/dashboard');
    }
}
