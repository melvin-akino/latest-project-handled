<?php

namespace App\Services;

use App\Http\Requests\OrderRequest;
use Illuminate\Support\Facades\{DB, Log};
use Exception;

class OrderService
{
    public static function getOrders(OrderRequest $request)
    {
        try
        {
            $where[] = ['o.user_id', auth()->user()->id];
            $dups = [];
            $whereOr = [];
            //filter out failed orders by default
            $where[] = ['o.status', '<>', 'FAILED'];
            $whereDate = [];
            if ($request->created_from)
            {
                $where[] = ['o.created_at', '>=', $request->created_from . ' 00:00:00'];
                $where[] = ['o.created_at', '<=', $request->created_to . ' 23:59:59'];
            }

            if ($request->settled_from)
            {
                $where[] = ['o.settled_at', '>=', $request->settled_from . ' 00:00:00'];
                $where[] = ['o.settled_at', '<=', $request->settled_to . ' 23:59:59'];
            }

            if ($request->status)
            {
                if ($request->status == 'open') {
                    $where[] = ['o.settled_date', null];
                }
                else {
                    $where[] = ['o.settled_date', '<>', null];
                }
            }

            if ($request->provider)
            {
                $where[] = ['p.id', $request->provider_id];
            }

            if ($request->provider_account_id)
            {
                $where[] = ['o.provider_account_id', $request->provider_account_id];
            }

            if ($request->league)
            {
                $where[] = ['o.master_league_name', 'like', $request->league."%"];
            }

            if ($request->team)
            {
                $whereOr[] = ['o.master_team_home_name', 'like', $request->team."%"];
                $whereOr[] = ['o.master_team_away_name', 'like', $request->team."%"];
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
                ->whereOr($whereOr)
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
                    'o.to_win',
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
                        'email'                 => $row->email,
                        'bet_identifier'        => $row->ml_bet_identifier,
                        'provider_id'           => $row->provider_id,
                        'provider'              => $row->provider,
                        'sport_id'              => $row->sport_id,
                        'sport'                 => $row->sport,
                        'bet_id'                => $row->bet_id,
                        'bet_selection'         => $row->bet_selection,
                        'username'              => $row->username,
                        'created_at'            => $row->created_at,
                        'status'                => $row->status,
                        'user_stake'            => $row->stake,
                        'user_towin'            => $row->to_win,
                        'user_pl'               => $row->profit_loss,
                        'actual_stake'          => $row->actual_stake,
                        'actual_to_win'         => $row->actual_to_win,
                        'actual_profit_loss'    => $row->actual_profit_loss,
                        'odds'                  => $row->odds,
                        'odds_label'            => $row->odd_label
                    ];

                    $dups[] = $row->id;
                }
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => !empty($transactions) ? $transactions : null
            ], 200);
        }
        catch (Exception $e)
        {
            Log::info('Viewing open orders failed.');
            Log::error($e->getMessage());
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'error'       => trans('responses.internal-error')
            ], 500);
        }

    }
}
