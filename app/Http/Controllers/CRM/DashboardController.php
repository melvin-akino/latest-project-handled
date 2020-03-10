<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
            'page_description' => "Lorem Ipsum dolor sit amet",
            'dashboard_menu'   => true,
        ];

        return view('CRM.dashboard')
            ->with($data);
    }
}
