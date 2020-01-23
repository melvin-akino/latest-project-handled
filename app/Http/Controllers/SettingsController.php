<?php

namespace App\Http\Controllers;

use App\Exceptions\ServerException;
use App\Http\Requests\SettingsRequests;

use App\Models\{ UserConfiguration, UserProviderConfiguration, UserSportOddConfiguration };
use App\User;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class SettingsController extends Controller
{
    public function postSettings($type, SettingsRequests $request)
    {
        try {
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

            $oddsConfig = ['bet-columns'];
            $provConfig = ['bookies'];
            $userConfig = ['general', 'trade-page', 'bet-slip', 'notifications-and-sounds', 'language'];

            if (in_array($type, $userConfig)) {
                $response = UserConfiguration::saveSettings($type, $request->all());
            } else if (in_array($type, $provConfig)) {
                $response = UserProviderConfiguration::saveSettings($type, $request->all());
            } else if (in_array($type, $oddsConfig)) {
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

            if (!$response) {
                throw new ServerException("DB Transaction error");
            }

            return response()->json([
                'status'        => true,
                'status_code'   => 200,
                'message'       => trans('notifications.save.success'),
            ]);
        } catch (ServerException $e) {
            return response()->json([
                'status'        => false,
                'status_code'   => 500,
                'message'       => trans('generic.internal-server-error')
            ]);
        }
    }

    /** CONFIRM APPROACH */
    protected function resetSettings()
    {
        UserConfiguration::where('user_id', auth()->user()->id)->delete();
        UserProviderConfiguration::where('user_id', auth()->user()->id)->delete();
        UserSportOddConfiguration::where('user_id', auth()->user()->id)->delete();
    }
}
