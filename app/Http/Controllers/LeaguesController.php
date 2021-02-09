<?php

namespace App\Http\Controllers;

use App\Facades\LeagueFacade;

class LeaguesController extends Controller
{
    public function list()
    {
        return LeagueFacade::list();
    }
}
