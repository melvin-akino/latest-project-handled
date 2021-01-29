<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsRequests;
use App\Models\{UserConfiguration, UserProviderConfiguration, UserSportOddConfiguration};
use App\Notifications\PasswordResetSuccess;
use Illuminate\Support\Facades\Log;
use App\User;
use Hash;
use Exception;

class SettingsController extends Controller
{
    protected $oddsConfig = ['bet-columns'];
    protected $provConfig = ['bookies'];
    protected $userConfig = ['general', 'trade-page', 'bet-slip', 'notifications-and-sounds', 'language'];

    public function postSettings($type, $sportId = null, SettingsRequests $request)
    {
        $response = true;

        try {
            if (in_array($type, $this->userConfig)) {
                $response = UserConfiguration::saveSettings($type, $request->all());
            } else if (in_array($type, $this->provConfig)) {
                $response = UserProviderConfiguration::saveSettings($request->all());
            } else if (in_array($type, $this->oddsConfig)) {
                $response = UserSportOddConfiguration::saveSettings($request->all(), $sportId);
            } else if ($type == 'profile') {
                User::find(auth()->user()->id)
                    ->update([
                        'firstname'  => $request->firstname,
                        'lastname'   => $request->lastname,
                        'address'    => $request->address,
                        'country_id' => $request->country_id,
                        'state'      => $request->state,
                        'city'       => $request->city,
                        'postcode'   => $request->postcode,
                        'phone'      => $request->phone,
                    ]);
            } else if ($type == 'change-password') {
                $user            = User::find(auth()->user()->id);
                $currentPassword = $user->password;

                if (Hash::check($request->old_password, $currentPassword)) {
                    if (Hash::check($request->password, $currentPassword)) {
                        $toLogs = [
                            "class"       => "SettingsController",
                            "message"     => trans('passwords.change.unique'),
                            "module"      => "API_ERROR",
                            "status_code" => 400,
                        ];
                        monitorLog('monitor_api', 'error', $toLogs);

                        return response()->json([
                            'status'      => false,
                            'status_code' => 400,
                            'message'     => trans('passwords.change.unique')
                        ], 400);
                    }

                    /** Update Authenticated User's Password with applied encryption */
                    $response = User::find($user->id)
                        ->update(['password' => bcrypt($request->password)]);

                    /** Notify Authenticated User via e-mail that there has been an update with their password */
                    $user->notify(new PasswordResetSuccess($user));
                } else {
                    $toLogs = [
                        "class"       => "SettingsController",
                        "message"     => trans('passwords.current.incorrect'),
                        "module"      => "API_ERROR",
                        "status_code" => 400,
                    ];
                    monitorLog('monitor_api', 'error', $toLogs);

                    return response()->json([
                        'status'      => false,
                        'status_code' => 400,
                        'message'     => trans('passwords.current.incorrect')
                    ], 400);
                }
            } else if ($type == 'reset') {
                $this->resetSettings();
            } else {
                $toLogs = [
                    "class"       => "SettingsController",
                    "message"     => trans('generic.not-found'),
                    "module"      => "API_ERROR",
                    "status_code" => 404,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'      => false,
                    'status_code' => 404,
                    'message'     => trans('generic.not-found'),
                ], 404);
            }

            if (!$response) {
                $toLogs = [
                    "class"       => "SettingsController",
                    "message"     => trans('generic.bad-request'),
                    "module"      => "API_ERROR",
                    "status_code" => 400,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                return response()->json([
                    'status'      => false,
                    'status_code' => 400,
                    'message'     => trans('generic.bad-request')
                ], 400);
            }

            $toLogs = [
                "class"       => "SettingsController",
                "message"     => trans('notifications.save.success'),
                "module"      => "API",
                "status_code" => 200,
            ];
            monitorLog('monitor_api', 'info', $toLogs);

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'message'     => trans('notifications.save.success')
            ], 200);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "SettingsController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
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
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "SettingsController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    protected function resetSettings(): bool
    {
        try {
            foreach ($this->userConfig AS $config) {
                UserConfiguration::saveSettings($config, config('default_config.' . $config));
            }

            UserProviderConfiguration::saveSettings(config('default_config.bookies.disabled_bookies'));
            UserSportOddConfiguration::saveSettings(config('default_config.bet-columns.disabled_columns'));

            return true;
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "SettingsController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return false;
        }
    }
}
