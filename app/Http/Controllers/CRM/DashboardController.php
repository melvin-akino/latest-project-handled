<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;

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

        $data = [
            'page_title'       => "Dashboard",
            'page_description' => "9pine CRM tools",
            'dashboard_menu'   => true,
            'total_accounts'   => User::count(),
            'registered_today' => User::getRegisteredToday()->count()
        ];

        return view('CRM.dashboard')
            ->with($data);
    }

    public function testSWT()
    {
        $data = [
            'page_title'       => "Dashboard",
            'page_description' => "9pine CRM tools",
            'dashboard_menu'   => true,
            'total_accounts'   => User::count(),
            'registered_today' => User::getRegisteredToday()->count()
        ];

        return view('CRM.test')
            ->with($data);
    }

    public function checkSWT(Request $request)
    {
        $data   = [];
        $server = app('swoole');
        $table  = $request->swtable . "Table";
        $swt    = $server->{$table};

        foreach ($swt AS $key => $row) {
            $data[] = [
                'key'  => $key,
                'data' => $row,
            ];
        }

        return $data;
    }
}
