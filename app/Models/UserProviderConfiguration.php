<?php

namespace App\Models;

use App\Exceptions\ServerException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\Provider;

class UserProviderConfiguration extends Model
{
    protected $table = 'user_provider_configurations';

    protected $fillable = [
        'provider_id',
        'user_id',
        'punter_percentage',
        'active',
        'priority'
    ];

    /** NEW APPROACH */
    public static function saveSettings(array $request): bool
    {
        try {
            DB::beginTransaction();

            $requestProviders = array_column($request, 'provider_id');
            $providers        = Provider::getActiveProviders()
                ->get()
                ->toArray();

            foreach ($providers as $provider) {
                $active = true;
                if (!empty($request)) {
                    if (in_array($provider['id'], $requestProviders)) {
                        $requestProviderKey = array_search($provider['id'], $requestProviders);
                        $active = $request[$requestProviderKey]['active'];
                        self::updateOrCreate(
                            [
                                'user_id' => auth()->user()->id,
                                'provider_id' => $request[$requestProviderKey]['provider_id'],
                            ],
                            [
                                'active' => $active,
                                'updated_at' => Carbon::now()
                            ]
                        );
                    }
                } else {
                    self::updateOrCreate(
                        [
                            'user_id' => auth()->user()->id,
                            'provider_id' => $provider['id'],
                        ],
                        [
                            'active' => $active,
                            'updated_at' => Carbon::now()
                        ]
                    );
                }

                if (empty($_SERVER['_PHPUNIT'])) {
                    $swoole = app('swoole')->userProviderConfigTable;
                    $swooleId = implode(':', [
                        "userId:" . auth()->user()->id,
                        "pId:"    . $provider['id']
                    ]);

                    if ($swoole->exists($swooleId)) {
                        $swoole[$swooleId]['active'] = $active;
                    } else {
                        $swoole->set($swooleId,
                            [
                                'user_id'     => auth()->user()->id,
                                'provider_id' => $provider['id'],
                                'active'      => $active,
                            ]
                        );
                    }
                }
            }

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw new ServerException($e->getMessage());
        }
    }

    public static function getInactiveProviders()
    {
        return self::where('active', false)
            ->where('user_id', auth()->user()->id)
            ->orderBy('provider_id', 'asc');
    }

    public static function getProviderIdList(int $userId)
    {
        $userProvider = self::where('user_id', $userId)
                         ->join('providers', 'provider_id', 'providers.id');
        if ($userProvider->exists()) {
            $userProvider = $userProvider->where('active', true)->orderBy('priority', 'ASC')->pluck('provider_id')->toArray();
        } else {
            $userProvider = Provider::where('is_enabled', true)->orderBy('priority', 'ASC')->pluck('id')->toArray();
        }
        return $userProvider;
    }
}
