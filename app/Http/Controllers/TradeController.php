<?php

namespace App\Http\Controllers;

use App\Models\{MasterEvent, MasterLeague, Sport, UserSelectedLeague, UserWatchlist};

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Faker\Factory AS Faker;

class TradeController extends Controller
{
    /**
     * Fetch Authenticated User's Lists of Open Orders
     *
     * @return json
     */
    public function getUserBetbar()
    {
        try {
            $data = [
                'status'      => true,
                'status_code' => 200,
                'data'        => [
                    "bet_id_20"        => [
                        'league_name' => "FIFA Asia 2020",
                        'home'        => "Vietnam",
                        'away'        => "South Korea",
                        'bet_info'    => [
                            'home',
                            'FT 1X2',
                            '1.54',
                            '120'
                        ],
                        'status'      => "Processing",
                        'created_at'  => "2020-02-11 4:20 PM",
                    ],
                    "bet_id_19"        => [
                        'league_name' => "FIFA Asia 2020",
                        'home'        => "Philippines",
                        'away'        => "India",
                        'bet_info'    => [
                            'away',
                            'FT 1X2',
                            '2.58',
                            '80'
                        ],
                        'status'      => "Success",
                        'created_at'  => "2020-02-11 4:10 PM",
                    ],
                ],
            ];

            return response()->json($data, 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /**
     * Add/Remove to Authenticated User's Lists of Favorite Leagues/Events
     *
     * @param  $action  "add|remove"
     * @param  $request \Illuminate\Http\Request
     * @return json
     */
    public function postManageWatchlist($action, Request $request)
    {
        try {
            /** TO DO: Include Logic for adding game events to User Watchlist */
            $data = [];
            $lang = "";

            switch ($request->type) {
                case 'league':
                    $leagueId = MasterLeague::getIdByName($request->data);

                    if ($leagueId) {
                        $masterEventIds = MasterEvent::getActiveEvents('master_league_id', '=', $leagueId)->get('id')->toArray();
                    } else {
                        return response()->json([
                            'status'      => false,
                            'status_code' => 404,
                            'message'     => trans('generic.not-found')
                        ], 404);
                    }
                    break;

                case 'event':
                    $masterEventIds = MasterEvent::getActiveEvents('master_event_unique_id', '=', $request->data)->get('id')->toArray();
                    break;
            }

            if ($action == "add") {
                $lang = "added";

                foreach ($masterEventIds AS $row) {
                    UserWatchlist::create(
                        [
                            'user_id'         => auth()->user()->id,
                            'master_event_id' => $row
                        ]
                    );
                }
            }

            if ($action == "remove") {
                $lang = "removed";

                UserWatchlist::where('user_id', auth()->user()->id)
                    ->whereIn('master_event_id', $masterEventIds)
                    ->delete();
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'message'     => trans('game.watchlist.' . $lang)
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /**
     * Get Leagues per Authenticated User's default Sport
     *
     * @return json
     */
    public function getInitialLeagues()
    {
        try {
            /** Get Authenticated User's Default Initial Sport : Last Sport visited */
            $data = getUserDefault(auth()->user()->id, 'sport');

            /** Temporary Dummy Data */
            $leagues = $this->loopLeagues($data['default_sport']);

            if (!$data['status']) {
                return response()->json([
                    'status'      => false,
                    'status_code' => 400,
                    'message'     => $data['error']
                ]);
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'sport_id'    => $data['default_sport'],
                'data'        => $leagues
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /**
     * Temporary produce dummy data for Multiline Leagues
     *
     * @param  int    $sportId
     * @return json
     */
    private function loopLeagues(int $sportId)
    {
        $data = [];
        $faker = Faker::create();
        $sportName = Sport::find($sportId)->details;
        $gameSchedules = [
            'inplay',
            'today',
            'early'
        ];

        foreach ($gameSchedules AS $row) {
            for ($i = 0; $i < rand(2, 12); $i++) {
                $data[$row][] = [
                    'name'        => $faker->country . " " . $sportName,
                    'match_count' => rand(1, 8),
                ];
            }
        }

        return $data;
    }

    /**
     * Add/Remove Authenticated User's Selected Sidebar Leagues
     *
     * @param  $request \Illuminate\Http\Request
     * @return json
     */
    public function postManageSidebarLeagues(Request $request)
    {
        try {
            $leagueId   = MasterLeague::getIdByName($request->data);
            $checkTable = UserSelectedLeague::where('user_id', auth()->user()->id)
                ->where('master_league_id', $leagueId);

            if ($checkTable->count() == 0) {
                UserSelectedLeague::create(
                    [
                        'user_id'          => auth()->user()->id,
                        'master_league_id' => $leagueId
                    ]
                );
            } else {
                $checktable->delete();
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'message'     => trans('notifications.save.success')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }
}
