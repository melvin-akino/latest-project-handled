<?php

namespace App\Http\Controllers;

use App\Exceptions\ServerException;
use Throwable;
use App\Http\Requests\SettingsRequests;

use App\Models\{UserConfiguration, UserProviderConfiguration, UserSportOddConfiguration};
use App\User;
use Hash;

class SettingsController extends Controller
{
    protected $oddsConfig = ['bet-columns'];
    protected $provConfig = ['bookies'];
    protected $userConfig = ['general', 'trade-page', 'bet-slip', 'notifications-and-sounds', 'language'];

    public function postSettings($type, SettingsRequests $request)
    {
        try {
            if (in_array($type, $this->userConfig)) {
                UserConfiguration::saveSettings($type, $request->all());
            } else if (in_array($type, $this->provConfig)) {
                UserProviderConfiguration::saveSettings($request->all());
            } else if (in_array($type, $this->oddsConfig)) {
                UserSportOddConfiguration::saveSettings($request->all());
            } else if ($type == 'profile') {
                User::find(auth()->user()->id)
                    ->update([
                        'firstname'             => $request->firstname,
                        'lastname'              => $request->lastname,
                        'address'               => $request->address,
                        'country_id'            => $request->country_id,
                        'state'                 => $request->state,
                        'city'                  => $request->city,
                        'postcode'              => $request->postcode,
                        'phone'                 => $request->phone,
                        'currency_id'           => $request->currency_id,
                    ]);
            } else if ($type == 'change-password') {
                $currentPassword = User::find(auth()->user()->id)->password;

                if (Hash::check($request->old_password, $currentPassword)) {
                    if (Hash::check($request->password, $currentPassword)) {
                        return response()->json([
                            'status'      => false,
                            'status_code' => 400,
                            'message'     => trans('passwords.change.unique')
                        ], 400);
                    }

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
                $this->resetSettings();
            } else {
                return response()->json([
                    'status'        => false,
                    'status_code'   => 404,
                    'message'       => trans('generic.not-found'),
                ], 404);
            }

            return response()->json([
                'status'        => true,
                'status_code'   => 200,
                'message'       => trans('notifications.save.success'),
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'        => false,
                'status_code'   => 500,
                'message'       => trans('generic.internal-server-error')
            ], 500);
        }
    }

    public function getSettings(string $type)
    {
        try {
            $settings[$type] = config('default_config.' . $type);

            if (in_array($type, ['general', 'trade-page', 'bet-slip', 'notifications-and-sounds', 'language'])) {
                $settings = UserConfiguration::getUserConfigByMenu(auth()->user()->id, $type, $settings);
            } else {
                $settings = UserConfiguration::getUserConfigBookiesAndBetColumns($settings);
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $settings[$type],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'        => false,
                'status_code'   => 500,
                'message'       => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /** CONFIRM APPROACH */
    protected function resetSettings(): bool
    {
        foreach ($this->userConfig AS $config) {
            UserConfiguration::saveSettings($config, config('default_config.' . $config));
        }

        UserProviderConfiguration::saveSettings(config('default_config.bookies.disabled_bookies'));
        UserSportOddConfiguration::saveSettings(config('default_config.bet-columns.disabled_columns'));

        return true;
    }
}
