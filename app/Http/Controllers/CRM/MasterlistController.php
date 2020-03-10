<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MasterlistController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:crm');
    }

    public function batchMatching()
    {
        $data = [
            'page_title'          => "Batch Matching",
            'page_description'    => "Human Intervention Multiple Game Events Matching",
            'masterlist_menu'     => true,
            'batch_matching_menu' => true,
        ];

        return view('CRM.masterlist.batch_matching')
            ->with($data);
    }

    public function dataTables(Request $request)
    {
        return dataTable($request, Events::with('sports', 'providers'));
    }
}
