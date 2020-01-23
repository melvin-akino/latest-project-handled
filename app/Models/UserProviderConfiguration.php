<?php

namespace App\Models;

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

            $providers = Provider::getActiveProviders()
                ->get()
                ->toArray();

            $requestProviders = array_column($request, 'provider_id');

            foreach ($providers as $provider) {
                if (in_array($provider['id'], $requestProviders)) {
                    $requestProviderKey = array_search($provider['id'], $requestProviders);

                    self::updateOrCreate(
                        [
                            'user_id' => auth()->user()->id,
                            'provider_id' => $request[$requestProviderKey]['provider_id'],
                        ],
                        [
                            'active' => $request[$requestProviderKey]['active']
                        ]
                    );
                }
            }

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            return false;
        }
    }

    public static function getInactiveProviders()
    {
        return self::where('active', false)
            ->where('user_id', auth()->user()->id)
            ->orderBy('provider_id', 'asc');
    }
}
