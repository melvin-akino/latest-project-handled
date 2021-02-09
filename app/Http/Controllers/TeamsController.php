<?php

namespace App\Http\Controllers;

use App\Facades\TeamFacade;

class TeamsController extends Controller
{
    public function list()
    {
        return TeamFacade::list();
    }
}
