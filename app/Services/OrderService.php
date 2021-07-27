<?php

namespace App\Services;

use App\Models\{OddType, Timezones, UserConfiguration};
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
            $data    = DB::table('orders AS o')
                ->join('sports AS s', 's.id', '=', 'o.sport_id')
                ->join('provider_accounts AS pa', 'pa.id', '=', 'o.provider_account_id')
                ->join('providers AS p', 'p.id', '=', 'pa.provider_id')
                ->join('users AS u', 'u.id', '=', 'o.user_id')
                ->join('order_logs AS ol', 'ol.order_id', '=', 'o.id')
                ->join('provider_account_orders AS pao', 'pao.order_log_id', '=', 'ol.id')
                ->join('odd_types AS ot', 'ot.id', '=', 'o.odd_type_id')
                ->leftJoin('provider_error_messages as pem', 'pem.id','=', 'o.provider_error_message_id' )
                ->leftJoin('error_messages AS em', 'em.id', '=', 'pem.error_message_id');

            if (!empty($where)) {
                $data = $data->where($where);

                if (!empty($request->date_from)) {
                    $requestFrom = Carbon::createFromFormat("Y-m-d", $request->date_from, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d") . " 00:00:00";
                    $requestTo   = Carbon::createFromFormat("Y-m-d", $request->date_to, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d") . " 23:59:59";

                    if ($request->period == "daily") {
                        $data = $data->whereBetween(DB::raw("o.created_at AT TIME ZONE 'UTC' AT TIME ZONE '$userTz'"), [ $request->date_from . " 00:00:00", $request->date_from . " 23:59:59" ]);
                    } else {
                        $data = $data->whereBetween(DB::raw("o.created_at AT TIME ZONE 'UTC' AT TIME ZONE '$userTz'"), [ $requestFrom, $requestTo ]);
                    }
                }

                if (!empty($request->search_by)) {
                    switch ($request->search_by) {
                        case "league_names":
                            $data = $data->where('o.master_league_name', 'ILIKE', trim(str_replace('%', '^', $request->search_keyword)) . "%");
                        break;
                        case "team_names":
                            $data = $data->where(function ($query) use ($request) {
                                $query->where('o.master_team_home_name', 'ILIKE', $request->search_keyword . "%")
                                    ->orWhere('o.master_team_away_name', 'ILIKE', $request->search_keyword . "%");
                            });
                        break;
                    }
                }
            }

            $data = $data->orderBy('o.id', 'ASC')
                ->orderBy('pao.order_log_id', 'DESC')
                ->distinct()
                ->get([
                    'o.id',
                    'o.odd_type_id',
                    'ot.type as odd_type',
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
                    'o.settled_date',
                    'o.reason',
                    'o.status',
                    'o.stake',
                    'o.to_win',
                    'o.score_on_bet',
                    'o.final_score',
                    'o.profit_loss',
                    'o.master_league_name',
                    'o.master_team_home_name',
                    'o.master_team_away_name',
                    'o.master_event_unique_id',
                    'o.master_event_market_unique_id',
                    'pao.actual_stake',
                    'pao.actual_to_win',
                    'pao.actual_profit_loss',
                    'o.odds',
                    'o.odd_label',
                    'em.error',
                    'o.market_flag',
                ]);

            $ouLabels = OddType::where('type', 'LIKE', '%OU%')->pluck('id')->toArray();
            $oeLabels = OddType::where('type', 'LIKE', '%OE%')->pluck('id')->toArray();

            foreach ($data as $row) {
                if (!in_array($row->id, $dups)) {
                    $created = Carbon::createFromFormat("Y-m-d H:i:s", $row->created_at, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s");
                    $settled = empty($row->settled_date) ? "" : Carbon::createFromFormat("Y-m-d H:i:sO", $row->settled_date, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s");

                    $scorePrefix = strpos($row->odd_type, 'HT ') !== false ? 'HT ' : 'FT ';
                    if (!empty($row->settled_date) && !empty($row->final_score)) {
                        $score = array_map('trim', explode('-', $row->final_score));
                    } else {
                        $score = array_map('trim', explode('-', $row->score_on_bet));
                    }

                    if (strtoupper($row->market_flag) == "DRAW") {
                        $teamname = "DRAW";
                    } else {
                        $objectKey = "master_team_" . strtolower($row->market_flag) . "_name";
                        $teamname  = $row->{$objectKey};
                    }

                    if (in_array($row->odd_type_id, $ouLabels)) {
                        $ou        = explode(' ', $row->odd_label)[0];
                        $teamname  = $ou == "O" ? "Over" : "Under";
                        $teamname .= " " . explode(' ', $row->odd_label)[1];
                    }

                    if (in_array($row->odd_type_id, $oeLabels)) {
                        $teamname  = $row->odd_label == "O" ? "Odd" : "Even";
                    }

                    $origBetSelection = explode(PHP_EOL, $row->bet_selection);
                    $betSelection     = implode("\n", [
                        $row->master_team_home_name . " vs " . $row->master_team_away_name,
                        $teamname . " @ " . $row->odds,
                        end($origBetSelection),
                    ]);

                    if (in_array($row->odd_type_id, $ouLabels) || in_array($row->odd_type_id, $oeLabels)) {
                        $lastLineBetSelection = end($origBetSelection);
                        $betPeriod            = strpos($lastLineBetSelection, "FT") !== false ? "FT " : (strpos($lastLineBetSelection, "HT") !== false ? "HT " : "");
                        $betSelectionScore    = explode('(', $lastLineBetSelection);
                        $betSelection         = implode("\n", [
                            $row->master_team_home_name . " vs " . $row->master_team_away_name,
                            $betPeriod . $teamname . " @ " . $row->odds . " (" . $betSelectionScore[1],
                        ]);
                    }

                    $transactions[] = [
                        'order_id'      => $row->id,
                        'odd_type_id'   => $row->odd_type_id,
                        'date'          => date("Y-m-d", strtotime($created)),
                        'leaguename'    => $row->master_league_name,
                        'bet_id'        => $row->ml_bet_identifier,
                        'provider'      => strtoupper($row->provider),
                        'bet_selection' => nl2br($betSelection),
                        'created'       => $created,
                        'settled'       => $settled,
                        'status'        => $row->status,
                        'stake'         => $row->stake,
                        'valid_stake'   => $row->profit_loss ? abs($row->profit_loss) : 0,
                        'towin'         => $row->to_win,
                        'score'         => $scorePrefix . (string) $score[0] . " - " . $score[1],
                        'home_score'    => $score[0],
                        'away_score'    => $score[1],
                        'pl'            => (string) $row->profit_loss,
                        'reason'        => $row->reason,
                        'betData'       => $row->reason,
                        'error_message' => $row->error,
                        'odds'          => $row->odds,
                        'points'        => $row->odd_label,
                        'event_id'      => $row->master_event_unique_id,
                        'market_id'     => $row->master_event_market_unique_id
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

    public function postRetryBet(Request $request)
    {
        try {
            $retryTypes = RetryType::pluck('type')->toArray();
            $orderData  = Order::retryBetData($request->order_id);

            if (in_array($request->retry_type, $retryTypes)) {
                switch (strtolower($request->retry_type)) {
                    case 'manual-same-account':
                        retryCacheToRedis($orderData->toArray());
                    break;
                }
            } else {
                $toLogs = [
                    "class"       => "OrdersController",
                    "message"     => trans('generic.bad-request'),
                    "module"      => "API_ERROR",
                    "status_code" => 400,
                ];
                monitorLog('monitor_api', 'error', $toLogs);

                throw new BadRequestException(trans('generic.bad-request'));
            }
        } catch (BadRequestException $e) {
            $toLogs = [
                "class"       => "OrdersController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => 400,
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 400,
                'message'     => $e->getMessage()
            ], 400);
        } catch (NotFoundException $e) {
            $toLogs = [
                "class"       => "OrdersController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => 404,
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 404,
                'message'     => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "OrdersController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }
}
