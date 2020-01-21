<?php

namespace App\Http\Controllers;

use App\Models\Timezones;
use Illuminate\Http\Request;

class ResourcesController extends Controller
{
    public function getTimezones() {
        $timezones = Timezones::getAll();

        return response()->json([
            'status'        => true,
            'status_code'   => 200,
            'data'          => $timezones,
        ]);
    }
}