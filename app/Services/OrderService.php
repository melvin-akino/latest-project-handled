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
            $where[] = ['ub.user_id', auth()->user()->id];
            $data    = DB::table('user_bets AS ub')
                ->join('odd_types AS ot', 'ot.id', '=', 'ub.odd_type_id')
                ->join('sport_odd_type as sot', 'ot.id', 'sot.odd_type_id');

            if (!empty($where)) {
                $data = $data->where($where);

                if (!empty($request->date_from)) {
                    $requestFrom = Carbon::createFromFormat("Y-m-d", $request->date_from, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d") . " 12:00:00";
                    $requestTo   = Carbon::createFromFormat("Y-m-d", $request->date_to, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d") . " 11:59:59";

                    if ($request->period == "daily") {
                        $data = $data->whereBetween(DB::raw("ub.created_at AT TIME ZONE 'UTC' AT TIME ZONE '$userTz'"), [ $request->date_from . " 00:00:00", $request->date_from . " 23:59:59" ]);
                    } else {
                        $data = $data->whereBetween(DB::raw("ub.created_at AT TIME ZONE 'UTC' AT TIME ZONE '$userTz'"), [ $requestFrom, $requestTo ]);
                    }
                }

                if (!empty($request->search_by)) {
                    switch ($request->search_by) {
                        case "league_names":
                            $data = $data->where('ub.master_league_name', 'ILIKE', trim(str_replace('%', '^', $request->search_keyword)) . "%");
                        break;
                        case "team_names":
                            $data = $data->where(function ($query) use ($request) {
                                $query->where('ub.master_team_home_name', 'ILIKE', $request->search_keyword . "%")
                                    ->orWhere('ub.master_team_away_name', 'ILIKE', $request->search_keyword . "%");
                            });
                        break;
                    }
                }
            }

            $data = $data->orderBy('ub.id', 'ASC')
                ->distinct()
                ->get([
                    'ub.id',
                    'ml_bet_identifier',
                    'ub.created_at',
                    'master_event_unique_id',
                    'mem_uid',
                    'master_league_name',
                    'master_team_home_name',
                    'master_team_away_name',
                    'market_flag',
                    'ub.odd_type_id',
                    'ot.type as odd_type',
                    'sot.name as column_type',
                    'stake',
                    'odds',
                    'odds_label',
                    'status',
                    'score_on_bet',
                    'final_score',
                    DB::raw('(SELECT SUM(to_win) FROM provider_bets WHERE user_bet_id = ub.id) as to_win'),
                    DB::raw('(SELECT SUM(profit_loss) FROM provider_bets WHERE user_bet_id = ub.id) as profit_loss'),
                ]);

            $ouLabels = OddType::where('type', 'LIKE', '%OU%')->pluck('id')->toArray();
            $oeLabels = OddType::where('type', 'LIKE', '%OE%')->pluck('id')->toArray();

            foreach ($data as $row) {
                if (!in_array($row->id, $dups)) {
                    $created = Carbon::createFromFormat("Y-m-d H:i:s", $row->created_at, 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d H:i:s");

                    $scorePrefix = strpos($row->odd_type, 'HT ') !== false ? 'HT ' : 'FT ';
                    if (!empty($row->final_score)) {
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
                        $ou        = explode(' ', $row->odds_label)[0];
                        $teamname  = $ou == "O" ? "Over" : "Under";
                        $teamname .= " " . explode(' ', $row->odds_label)[1];
                    }

                    if (in_array($row->odd_type_id, $oeLabels)) {
                        $teamname  = $row->odds_label == "O" ? "Odd" : "Even";
                    }

                    $betSelection     = implode("\n", [
                        $row->master_team_home_name . " vs " . $row->master_team_away_name,
                        $teamname . " @ " . $row->odds,
                        $row->column_type. " ". $row->odds_label ."(" . $row->score_on_bet .")"
                    ]);

                    if (in_array($row->odd_type_id, $ouLabels) || in_array($row->odd_type_id, $oeLabels)) {
                        $betPeriod            = strpos($row->column_type, "FT") !== false ? "FT " : (strpos($row->column_type, "HT") !== false ? "HT " : "");
                        $betSelection         = implode("\n", [
                            $row->master_team_home_name . " vs " . $row->master_team_away_name,
                            $betPeriod . $teamname . " @ " . $row->odds ."(" . $row->score_on_bet .")"
                        ]);
                    }

                    $transactions[] = [
                        'order_id'      => $row->id,
                        'odd_type_id'   => $row->odd_type_id,
                        'date'          => date("Y-m-d", strtotime($created)),
                        'leaguename'    => $row->master_league_name,
                        'bet_id'        => $row->ml_bet_identifier,
                        'bet_selection' => nl2br($betSelection),
                        'created'       => $created,
                        'status'        => $row->status,
                        'stake'         => $row->stake,
                        'valid_stake'   => $row->profit_loss ? abs($row->profit_loss) : 0,
                        'towin'         => $row->to_win,
                        'score'         => !empty($row->final_score) ? $scorePrefix . (string) $score[0] . " - " . $score[1] : "",
                        'home_score'    => $score[0],
                        'away_score'    => $score[1],
                        'pl'            => (string) $row->profit_loss,
                        'odds'          => $row->odds,
                        'points'        => $row->odds_label,
                        'event_id'      => $row->master_event_unique_id,
                        'market_id'     => $row->mem_uid
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
