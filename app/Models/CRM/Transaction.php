<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = "";

    protected $fillable = [
        'order_logs_id',
        'user_id',
        'source_id',
        'currency_id',
        'wallet_ledger_id',
        'provider_account_id',
        'reason',
        'amount',
    ];


    public function getTransactions($options)
    {
        $dups = [];
        //filter our failed orders by default
        $where[] = ['o.bet_id', '!=', ""];
        if (!empty($options)) {
            foreach($options as $key=>$value) {
                switch($key) {
                    case 'date_from' :
                        $where[] = ['o.created_at', '>=', $value];
                        break;
                    case 'date_to' :
                        $where[] = ['o.created_at', '<=', $value];
                        break;
                    case 'settled_from' :
                        $where[] = ['o.settled_at', '>=', $value];
                        break;
                    case 'settled_to' :
                        $where[] = ['o.settled_at', '<=', $value];
                        break;
                    case 'settled' :
                        $where[] = ['o.settled_at', '<>', ''];
                        break;
                    case 'open' :
                        $where[] = ['o.settled_at', '==', ''];
                        break;
                    case 'provider' :
                        $where[] = ['p.id', '<=', $value];
                        break;
                    case 'provider_account_id' :
                        $where[] = ['o.provider_account_id', '==', $value];
                        break;
                    default:
                        break;
                }
            }    
        }
        
        $data = DB::table('orders AS o')
            ->join('provider_accounts AS pa', 'pa.id', '=', 'o.provider_account_id')
            ->join('users AS u', 'u.id', '=', 'o.user_id')
            ->join('order_logs AS ol', 'ol.order_id', '=', 'o.id')
            ->join('provider_account_orders AS pao', 'pao.order_log_id', '=', 'ol.id')
            ->join('odd_types AS ot', 'ot.id', '=', 'o.odd_type_id')
            ->where($where)
            ->orderBy('o.id', 'ASC')
            ->orderBy('pao.order_log_id', 'DESC')
            ->distinct()
            ->get([
                'o.id',
                'pao.order_log_id',
                'u.email',
                'o.ml_bet_identifier',
                'o.bet_id',
                'pa.username',
                'o.created_at',
                'o.status',
                'o.stake',
                'o.profit_loss',
                'pao.actual_stake',
                'pao.actual_profit_loss',
                'o.odds',
                'o.odd_label'
            ]);


        foreach ($data as $row) {
            if (!in_array($row->id, $dups)) {
                $transactions = [
                    $row->email,
                    $row->ml_bet_identifier,
                    $row->bet_id,
                    $row->username,
                    $row->created_at,
                    $row->status,
                    $row->stake,
                    $row->profit_loss,
                    $row->actual_stake,
                    $row->actual_profit_loss,
                    $row->odds,
                    $row->odd_label
                ];

                $dups[] = $row->id;
            }
        }

        return $transactions;
    }
}