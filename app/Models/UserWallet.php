<?php

namespace App\Models;

use App\User;

use Exception;
use Illuminate\Database\Eloquent\Model;

class UserWallet extends Model
{
    protected $table    = "wallet";
    protected $fillable = [
        'balance',
        'currency_id',
        'user_id'
    ];

    const TYPE_CHARGE           = 'Credit';
    const TYPE_DISCHARGE        = 'Debit';
    const ERR_WALLET_DEDUCT     = 'Wallet Deduction Exceeded';
    const ERR_NEW_WALLET_DEDUCT = 'Currency not Set';

    public function Order()
    {
        return $this->hasMany('App\Models\Order', 'user_id', 'user_id');
    }

    public function account()
    {
        return $this->belongsTo(User::class, 'user_id')->select([
            "id",
            "firstname",
            "lastname",
            "email",
            "name",
        ]);
    }

    public function wallet_ledger()
    {
        return $this->hasMany(WalletLedger::class, 'wallet_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public static function makeTransaction($receiver, $amount, $currency, $source, $type)
    {
        $receiver = User::find($receiver);
        $wallet   = null;
        $debit    = doubleval(0);
        $credit   = doubleval(0);

        if (!$receiver->wallet()->count()) {
            if ($type == self::TYPE_DISCHARGE) {
                // no account yet but already deducted
                throw new Exception(self::ERR_NEW_WALLET_DEDUCT);
            }

            $wallet = $receiver->wallet()->create([
                'balance'     => $amount,
                'currency_id' => $currency
            ]);
        } else {
            $wallet = $receiver->wallet()->where('currency_id', $currency)->first();

            if (is_null($wallet)) {
                $wallet = $receiver->wallet()->create([
                    'balance'     => $amount,
                    'currency_id' => $currency
                ]);
            } else {
                if ($type == self::TYPE_CHARGE) {
                    $wallet->balance += $amount;
                } else {
                    if ($wallet->balance < $amount) {
                        throw new Exception(self::ERR_WALLET_DEDUCT);
                    }

                    $wallet->balance -= $amount;
                }

                $wallet->save();
            }
        }

        if ($type == self::TYPE_CHARGE) {
            $credit = $amount;
        } else {
            $debit = $amount;
        }

        return WalletLedger::create([
            'wallet_id' => $wallet->id,
            'source_id' => $source,
            'debit'     => $debit,
            'credit'    => $credit,
            'balance'   => $wallet->balance
        ]);
    }
}
