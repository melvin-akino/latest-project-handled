<?php

namespace App\Models;

use App\Exceptions\ServerException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

use Exception;
use Illuminate\Support\Facades\DB;

class UserProviderConfiguration extends Model
{
    protected $table = 'user_provider_configurations';

    protected $fillable = [
        'provider_id',
        'user_id',
        'punter_percentage',
        'active'
    ];

    /** NEW APPROACH */
    public static function saveSettings(array $request): bool
    {
        try {
            DB::beginTransaction();

            $requestProviders = array_column($request, 'provider_id');
            $swoole           = app('swoole')->UserProviderConfigTable;
            $providers        = Provider::getActiveProviders()
                ->get()
                ->toArray();

            foreach ($providers as $provider) {
                if (!empty($request)) {
                    if (in_array($provider['id'], $requestProviders)) {
                        $requestProviderKey = array_search($provider['id'], $requestProviders);
                        self::updateOrCreate(
                            [
                                'user_id' => auth()->user()->id,
                                'provider_id' => $request[$requestProviderKey]['provider_id'],
                            ],
                            [
                                'active' => $request[$requestProviderKey]['active'],
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
                            'active' => true,
                            'updated_at' => Carbon::now()
                        ]
                    );
                }

                $swooleId = implode(':', [
                    "userId:" . auth()->user()->id,
                    "pId:"    . $provider['id']
                ]);

                if ($swoole->exists($swooleId)) {
                    $swoole[$swooleId]['active'] = $request[$requestProviderKey]['active'];
                } else {
                    $swoole->set($swooleId,
                        [
                            'user_id'     => auth()->user()->id,
                            'provider_id' => $provider['id'],
                            'active'      => $request[$requestProviderKey]['active'],
                        ]
                    );
                }
            }

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw new ServerException(trans('generic.db-transaction-error'));
        }
    }

    public static function getInactiveProviders()
    {
        return self::where('active', false)
            ->where('user_id', auth()->user()->id)
            ->orderBy('provider_id', 'asc');
    }
}
