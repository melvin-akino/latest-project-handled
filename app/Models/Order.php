<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    public static function getAllOrders(int $userId)
    {
        return self::where('user_id', $userId)
            ->orderBy('created_at', 'desc');
    }

}