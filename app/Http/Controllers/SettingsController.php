<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsRequests;

use App\Models\{ UserConfiguration, UserProviderConfiguration, UserSportOddConfiguration };
use App\User;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function postSettings($type, SettingsRequests $request)
    {
        $submenus = [
            'general',
            'profile',
            'change-password',
            'trade-page',
            'bet-slip',
            'bookies',
            'bet-columns',
            'notifications-and-sounds',
            'language',
            'reset',
        ];

        $odds_config = ['bet-columns'];
        $prov_config = ['bookies'];
        $user_config = ['general', 'trade-page', 'bet-slip', 'notifications-and-sounds', 'language'];

        if (in_array($type, $user_config)) {
            $response = UserConfiguration::saveSettings($type, $request->all());
        } else if (in_array($type, $prov_config)) {
            $response = UserProviderConfiguration::saveSettings($type, $request->all());
        } else if (in_array($type, $odds_config)) {
            $response = UserSportOddConfiguration::saveSettings($type, $request->all());
        } else if ($type == 'profile') {
            $response = User::where('id', auth()->user()->id)
                ->update();
        } else if ($type == 'change-password') {
            // CHECK PASSWORD LOGIC

            $response = User::where('id', auth()->user()->id)
                ->update([ 'password' => bcrypt($request->new_password) ]);
        } else if ($type == 'reset') {
            // $this->resetSettings();
        } else {
            return response()->json([
                'status'        => false,
                'status_code'   => 404,
                'message'       => "URL does not exist",
            ]);
        }

        return response()->json([
            'status'        => true,
            'status_code'   => 200,
            'message'       => trans('notifications.save.success'),
        ]);
    }

    /** CONFIRM APPROACH */
    protected function resetSettings()
    {
        UserConfiguration::where('user_id', auth()->user()->id)->delete();
        UserProviderConfiguration::where('user_id', auth()->user()->id)->delete();
        UserSportOddConfiguration::where('user_id', auth()->user()->id)->delete();
    }
}