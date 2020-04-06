<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;


class SystemConfigurationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:crm');
    }

    public function list()
    {
        if (!session()->has('crm_user')) {
            return redirect()->route('crm');
        }

        $accountTypes = SystemConfiguration::getProviderAccountSettings();
        foreach ($accountTypes as $accountType) {
            $data['data'][] = [
                'id'                => $accountType['id'],
                'type'              => $accountType['type'],
            ];
        }

        return response()->json($data);
    }
}
