<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;
use App\Services\WalletService;
use App\Facades\WalletFacade;
use Carbon\Carbon;

class WalletToken implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    private static $walletClientsTable;
    private static $providers;
    private static $getAccessToken;
    private static $countToExpiration;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            if ($swoole->data2SwtTable->exist('data2Swt')) {
                Log::info("Wallet Token Starts");

                self::$walletClientsTable = app('swoole')->walletClientsTable;
                self::$providers          = app('swoole')->providersTable;
                self::$countToExpiration  = Carbon::now()->timestamp;
                
                self::connectAccess();

                while (!self::$quit) {

                    self::checkRefreshToken('ml');
                    
                    foreach (self::$providers as $key => $provider) {
                        if (!empty($provider['is_enabled'])) {
                            $providerAlias     = substr($key, strlen('providerAlias:'));
                            self::checkRefreshToken($providerAlias);
                        }
                    }

                    usleep(100000 * 60 * 60 * 24);
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

    }

    private static function checkRefreshToken(string $clientUsers)
    {
        $expiresIn         = self::$getAccessToken[$clientUsers]->data->expires_in;
        $refreshToken      = self::$getAccessToken[$clientUsers]->data->refresh_token;

        $timestampNow = Carbon::now()->timestamp;
        if (self::$countToExpiration + $expiresIn[$clientUsers] <= $timestampNow) {
            $getRefreshToken   = WalletFacade::refreshToken($refreshToken);
            self::$countToExpiration = $timestampNow;

            if ($getRefreshToken->status) {
                if (self::$countToExpiration + $getRefreshToken->data->expires_in <= $timestampNow) {
                    $getAccessToken = WalletFacade::getAccessToken('wallet');

                    $accessToken = $getAccessToken->data->access_token;
                    self::$walletClientsTable->set($clientUsers . '-users', [
                        'token' => $accessToken
                    ]);
                }
            } else {
                $getAccessToken = WalletFacade::getAccessToken('wallet');

                $accessToken = $getAccessToken->data->access_token;
                self::$walletClientsTable->set($clientUsers . '-users', [
                    'token' => $accessToken
                ]);
            }
        }
    }

    private static function connectAccess()
    {
        //ML User Get Access Token
        self::$getAccessToken['ml'] = WalletFacade::getAccessToken('wallet');
        $accessToken = self::$getAccessToken['ml']->data->access_token;
        self::$walletClientsTable->set('ml-users', [
            'token' => $accessToken
        ]);

        //Provider Get Access Token
        foreach (self::$providers as $key => $provider) {
            if (!empty($provider['is_enabled'])) {
                $providerAlias          = substr($key, strlen('providerAlias:'));
                $walletService          = new WalletService(config('wallet.url'), $providerAlias, md5($providerAlias));
                self::$getAccessToken[$providerAlias]         = $walletService->getAccessToken('wallet');
                
                $countToExpiration      = Carbon::now()->timestamp;
                
                $accessToken = self::$getAccessToken[$providerAlias]->data->access_token;
                self::$walletClientsTable->set($providerAlias . '-users', [
                    'token' => $accessToken
                ]);
            }
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
