<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Models\{UserWallet, Currency};
use Illuminate\Support\Arr;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'firstname',
        'lastname',
        'phone',
        'address',
        'country_id',
        'state',
        'city',
        'status',
        'postcode',
        'phone_country_code',
        'currency_id',
        'birthdate',
        'is_vip',
        'uuid',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function activeUser($userId)
    {
        return self::find($userId)->where('status', 1);
    }

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }

    public function wallet()
    {
        return $this->hasMany(UserWallet::class, 'user_id', 'id');
    }

    public function wallet_deposit()
    {
        return $this->hasMany(WalletDeposit::class, 'user_id', 'id');
    }

    public function scopeSearch($query, $search, $cols = null)
    {
        if (is_null($cols)) {
            $except = [
                array_search('status',      $this->fillable),
                array_search('password',    $this->fillable),
                array_search('birthdate',   $this->fillable),
                array_search('currency_id', $this->fillable),
                array_search('address',     $this->fillable),
                array_search('country_id',  $this->fillable),
                array_search('postcode',    $this->fillable),
                array_search('phone',       $this->fillable),
            ];

            $cols = Arr::except($this->fillable, $except);
        }

        foreach ($cols as $key => $value) {
            if ($key == 0) {
                $query->where($value, 'ILIKE', "%$search%");
            }
            $query->orWhere($value, 'ILIKE', "%$search%");
        }
        return $query;
    }

    public static function getRegisteredToday()
    {
        return self::whereDate('created_at', '=', date('Y-m-d'));
    }
}
