<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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


    public static function getTransactions($options)
    {
        $dups = [];
        //filter our failed orders by default
        $where[] = ['o.status', '<>', 'FAILED'];
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
                    case 'status' :
                        if ($value == 'open') {
                            $where[] = ['o.settled_date', null];
                        }
                        else {
                            $where[] = ['o.settled_date', '<>', null];
                        }                        
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
            ->join('sports AS s', 's.id', '=', 'o.sport_id')
            ->join('provider_accounts AS pa', 'pa.id', '=', 'o.provider_account_id')
            ->join('providers AS p', 'p.id', '=', 'pa.provider_id')
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
                'p.id as provider_id',
                'p.alias as provider',
                's.id as sport_id',
                's.sport',
                'o.bet_selection',
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
                'pao.actual_to_win',
                'pao.actual_profit_loss',
                'o.odds',
                'o.odd_label'
            ]);


        foreach ($data as $row) {
            if (!in_array($row->id, $dups)) {
                $transactions[] = [
                    'email' => $row->email,
                    'bet_identifier' => $row->ml_bet_identifier,
                    'provider_id' => $row->provider_id,
                    'provider' => $row->provider,
                    'sport_id' => $row->sport_id,
                    'sport' => $row->sport,
                    'bet_id' => $row->bet_id,
                    'bet_selection' => $row->bet_selection,
                    'username' => $row->username,
                    'created_at' => $row->created_at,
                    'status' => $row->status,
                    'user_stake' => $row->stake,
                    'user_pl' => $row->profit_loss,
                    'actual_stake' => $row->actual_stake,
                    'actual_to_win' => $row->actual_to_win,
                    'actual_profit_loss' => $row->actual_profit_loss,
                    'odds' => $row->odds,
                    'odds_label' => $row->odd_label
                ];

                $dups[] = $row->id;
            }
        }

        return !empty($transactions) ? $transactions : [];
    }
}