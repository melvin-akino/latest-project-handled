<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Models\UserWallet;
use App\Models\Currency;

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
    public function wallet()
    {
        return $this->hasMany(UserWallet::class, 'user_id', 'id');
    }
    public function wallet_deposit()
    {
        return $this->hasMany(WalletDeposit::class, 'user_id', 'id');
    }
    /*
    public function primary_wallet()
    {
        $this->belongsto(UserWallet::class,'id','user_id');
    }
    public function my_currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }
    */
}
