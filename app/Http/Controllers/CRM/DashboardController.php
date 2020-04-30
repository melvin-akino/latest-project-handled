<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Redis;
class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:crm');
    }

    public function index()
    {
        if (!session()->has('crm_user')) {
            return redirect()->route('crm');
        }
        $f = Redis::hgetall('PROMETHEUS_:gauge:larvel_prom_users_online_total');
      echo 'dumping f';
        var_dump($f);

        $f = Redis::hgetall('PROMETHEUS_:gauge:larvel_prom_users_online_total');
      echo 'dumping f';
        var_dump($f);
        /*

        $data = [
            'page_title'       => "Dashboard",
            'page_description' => "9pine CRM tools",
            'dashboard_menu'   => true,
            'total_accounts'   => User::count(),
            'registered_today' => User::getRegisteredToday()->count()
        ];

        return view('CRM.dashboard')
            ->with($data);
            */
    }
}
