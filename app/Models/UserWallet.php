<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Models\{Currency,Source};
use App\Models\CRM\WalletLedger;


class UserWallet extends Model
{
	protected $table = "wallet";
	protected $fillable = [
        'balance',
        'currency_id',
        'user_id'
    ];
	
	const TYPE_CHARGE   		= 'Credit';
    const TYPE_DISCHARGE 		= 'Debit';
    const ERR_WALLET_DEDUCT 	= 'Wallet Deduction Exceeded';
    const ERR_NEW_WALLET_DEDUCT = 'Currency not Set';

	public function Order() {

		return $this->hasMany('App\Models\Order','user_id','user_id');

	}
	public static function makeTransaction(User $receiver, $amount, Currency $currency, Source $source, $type)
    {

        $wallet = null;
        $debit  = doubleval(0);
        $credit = doubleval(0);

        if (!$receiver->wallet()->count()) {

            if($type == self::TYPE_DISCHARGE) {
                // no account yet but already deducted
                throw new \Exception(self::ERR_NEW_WALLET_DEDUCT);
            }

            $wallet = $receiver->wallet()->create([
                'balance' 	  => $amount,
                'currency_id' => $currency->id
            ]);
        } else {
            $wallet = $receiver->wallet()->where('currency_id', $currency->id)->first();

            if (is_null($wallet)) {
                $wallet = $receiver->wallet()->create([
                    'balance' 	  => $amount,
                    'currency_id' => $currency->id
                ]);
            } else {
                if ($type == self::TYPE_CHARGE) {
                    $wallet->balance += $amount;
                } else {
                    if($wallet->balance < $amount){
                        throw new \Exception(self::ERR_WALLET_DEDUCT);
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
            'source_id' => $source->id,
            'debit' 	=> $debit,
            'credit' 	=> $credit,
            'balance' 	=> $wallet->balance
        ]);
    }
}
