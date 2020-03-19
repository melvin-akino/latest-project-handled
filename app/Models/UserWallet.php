<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWallet extends Model
{
    protected $table = "wallet";

    public static function getWallet($user_id){

        return self::where('user_id', $user_id);
 	}
 	public function Order(){
 	
 		 return $this->hasMany('App\Models\Order','user_id','user_id');
 	}
}
