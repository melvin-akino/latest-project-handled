<?php

namespace App\Http\Controllers;

use App\Exceptions\ServerException;
use App\Http\Requests\SettingsRequests;

use App\Models\{ UserConfiguration, UserProviderConfiguration, UserSportOddConfiguration };
use App\User;

class SettingsController extends Controller
{
    protected $oddsConfig = ['bet-columns'];
    protected $provConfig = ['bookies'];
    protected $userConfig = ['general', 'trade-page', 'bet-slip', 'notifications-and-sounds', 'language'];

    public function postSettings($type, SettingsRequests $request)
    {
        try {
            if (in_array($type, $this->userConfig)) {
                $response = UserConfiguration::saveSettings($type, $request->all());
            } else if (in_array($type, $this->provConfig)) {
                $response = UserProviderConfiguration::saveSettings($request->all());
            } else if (in_array($type, $this->oddsConfig)) {
                $response = UserSportOddConfiguration::saveSettings($request->all());
            } else if ($type == 'profile') {
                $response = User::find(auth()->user()->id)
                    ->update([
                        'firstname' => $request->firstname,
                        'lastname' => $request->lastname,
                        'address' => $request->address,
                        'country' => $request->country,
                        'state' => $request->state,
                        'city' => $request->city,
                        'postcode' => $request->postcode,
                        'phone_country_code' => $request->phone_country_code,
                        'phone' => $request->phone,
                        'currency_id' => $request->currency_id,
                    ]);
            } else if ($type == 'change-password') {
                $currentPassword = User::find(auth()->user()->id)->password;

                if (\Hash::check($request->old_password, $currentPassword)) {
                    $response = User::find(auth()->user()->id)
                        ->update([ 'password' => bcrypt($request->password) ]);
                } else {
                    return response()->json([
                        'status'        => false,
                        'status_code'   => 400,
                        'message'       => trans('passwords.current.incorrect'),
                    ]);
                }
            } else if ($type == 'reset') {
                $response = $this->resetSettings();
            } else {
                return response()->json([
                    'status'        => false,
                    'status_code'   => 404,
                    'message'       => "URL does not exist",
                ], 404);
            }

            if (!$response) {
                throw new ServerException("DB Transaction error");
            }

            return response()->json([
                'status'        => true,
                'status_code'   => 200,
                'message'       => trans('notifications.save.success'),
            ], 200);
        } catch (ServerException $e) {
            return response()->json([
                'status'        => false,
                'status_code'   => 500,
                'message'       => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /** CONFIRM APPROACH */
    protected function resetSettings()
    {
        try {
            foreach ($this->userConfig AS $config) {
                $response = UserConfiguration::saveSettings($config, config('default_config.' . $config));
            }

            foreach ($this->provConfig AS $config) {
                $response = UserProviderConfiguration::saveSettings(config('default_config.' . $config));
            }

            foreach ($this->oddsConfig AS $config) {
                $response = UserSportOddConfiguration::saveSettings(config('default_config.' . $config));
            }

            if (!$response) {
                throw new ServerException("DB Transaction error");
            }

            return true;
        } catch (ServerException $e) {
            return false;
        }
    }
}
