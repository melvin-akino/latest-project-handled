<?php

namespace App\Services;

use App\Models\{Timezones, UserConfiguration};
use App\Http\Requests\OrderRequest;
use Illuminate\Support\Facades\{DB, Log};

use Carbon\Carbon;
use Exception;

class OrderService
{
    public static function getOrders(OrderRequest $request)
    {
        try {
            $userTz        = "Etc/UTC";
            $getUserConfig = UserConfiguration::getUserConfig(auth()->user()->id)
                ->where('type', 'timezone')
                ->first();

            if (!is_null($getUserConfig)) {
                $userTz = Timezones::find($getUserConfig->value)->name;
            }

            $dups    = [];
            $where[] = ['o.user_id', auth()->user()->id];
            $whereOr = [];
            $data    = DB::table('orders AS o')
                ->join('sports AS s', 's.id', '=', 'o.sport_id')
                ->join('provider_accounts AS pa', 'pa.id', '=', 'o.provider_account_id')
                ->join('providers AS p', 'p.id', '=', 'pa.provider_id')
                ->join('users AS u', 'u.id', '=', 'o.user_id')
                ->join('order_logs AS ol', 'ol.order_id', '=', 'o.id')
                ->join('provider_account_orders AS pao', 'pao.order_log_id', '=', 'ol.id')
                ->join('odd_types AS ot', 'ot.id', '=', 'o.odd_type_id')
                ->join('error_messages AS em', 'em.id', '=', 'o.provider_error_message_id');

            if (!empty($where)) {
                $data        = $data->where($where);
                $requestFrom = Carbon::createFromFormat("Y-m-d H:i:s", $request->date_from . " 12:00:00", 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s");
                $requestTo   = Carbon::createFromFormat("Y-m-d H:i:s", $request->date_to . " 11:59:59", 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s");

                if (!empty($request->date_from)) {
                    $data = $data->whereBetween('o.created_at', [ $requestFrom, $requestTo ]);
                }

                if (!empty($request->search_by)) {
                    switch ($request->search_by) {
                        case "league_names":
                            $data = $data->where('o.master_league_name', 'ILIKE', trim(str_replace('%', '^', $request->search_keyword)) . "%");
                        break;
                        case "team_names":
                            $whereOr[] = ['o.master_team_home_name', 'ILIKE', $request->search_keyword . "%"];
                            $whereOr[] = ['o.master_team_away_name', 'ILIKE', $request->search_keyword . "%"];
                        break;
                    }
                }

                if (!empty($whereOr)) {
                    $data = $data->whereOr($whereOr);
                }
            }

            $data = $data->orderBy('o.id', 'ASC')
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
                    'o.reason',
                    'o.status',
                    'o.stake',
                    'o.to_win',
                    'o.score_on_bet',
                    'o.profit_loss',
                    'o.master_league_name',
                    'o.master_team_home_name',
                    'o.master_team_away_name',
                    'pao.actual_stake',
                    'pao.actual_to_win',
                    'pao.actual_profit_loss',
                    'o.odds',
                    'o.odd_label',
                    'em.error'
                ]);

            foreach ($data as $row) {
                if (!in_array($row->id, $dups)) {
                    $created        = Carbon::createFromFormat("Y-m-d H:i:s", $row->created_at, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s");
                    $transactions[] = [
                        'date'          => date("F d, Y", strtotime($created)),
                        'leaguename'    => $row->master_league_name,
                        'bet_id'        => $row->ml_bet_identifier,
                        'provider'      => $row->provider,
                        'bet_selection' => $row->bet_selection,
                        'created'       => $created,
                        'status'        => $row->status,
                        'stake'         => $row->stake,
                        'valid_stake'   => $row->to_win ? abs($row->to_win) : 0,
                        'towin'         => $row->to_win,
                        'score'         => (string) $row->score_on_bet,
                        'pl'            => $row->profit_loss,
                        'reason'        => $row->reason,
                        'betData'       => $row->reason,
                        'error_message' => $row->error,
                        'odds'          => $row->odds,
                        'odds_label'    => $row->odd_label
                    ];

                    $dups[] = $row->id;
                }
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => !empty($transactions) ? $transactions : null,
                'filters'     => [
                    'date_from'      => $request->date_from,
                    'date_to'        => $request->date_to,
                    'period'         => $request->period,
                    'group_by'       => $request->group_by,
                    'search_by'      => $request->search_by,
                    'search_keyword' => $request->search_keyword,
                ],
            ], 200);
        } catch (Exception $e) {
            Log::info('Viewing open orders failed.');
            Log::error($e);
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'error'       => trans('responses.internal-error')
            ], 500);
        }
    }
}