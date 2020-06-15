<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\EventsData;

class EventsDataController extends Controller
{
    public function __construct()
    {
    }

    public function index()
    {
        $data = EventsData::where('is_matched', false)->get();

        $table = '';
        foreach ($data as $swt) {
            $table .= "<tr>" .
                "<td>{$swt['sport_id']}</td>" .
                "<td>{$swt['provider_id']}</td>" .
                "<td>{$swt['league_name']}</td>" .
                "<td>{$swt['home_team_name']}</td>" .
                "<td>{$swt['away_team_name']}</td>" .
                "<td>{$swt['ref_schedule']}</td>" .
                "<td>{$swt['game_schedule']}</td>" .
                "<td>{$swt['event_identifier']}</td>" .
                "</tr>";
        }

        echo <<<EOF
            <h2>Unmatched Events</h2>
            <table border="1">
                <thead>
                <tr>
                    <th>SportId</th>
                    <th>ProviderId</th>
                    <th>League Name</th>
                    <th>Home Team Name</th>
                    <th>Away Team Name</th>
                    <th>Reference Schedule</th>
                    <th>Game Schedule</th>
                    <th>Event Identifier</th>
                </tr>
                </thead>
                <tbody>
                    {$table}
                </tbody>
            </table>
EOF;

        $data = EventsData::where('is_matched', true)->get();

        $table = '';
        foreach ($data as $swt) {
            $table .= "<tr>" .
                "<td>{$swt['sport_id']}</td>" .
                "<td>{$swt['provider_id']}</td>" .
                "<td>{$swt['league_name']}</td>" .
                "<td>{$swt['home_team_name']}</td>" .
                "<td>{$swt['away_team_name']}</td>" .
                "<td>{$swt['ref_schedule']}</td>" .
                "<td>{$swt['game_schedule']}</td>" .
                "<td>{$swt['event_identifier']}</td>" .
                "</tr>";
        }

        echo <<<EOF
            <h2>Matched Events</h2>
            <table border="1">
                <thead>
                <tr>
                    <th>SportId</th>
                    <th>ProviderId</th>
                    <th>League Name</th>
                    <th>Home Team Name</th>
                    <th>Away Team Name</th>
                    <th>Reference Schedule</th>
                    <th>Game Schedule</th>
                    <th>Event Identifier</th>
                </tr>
                </thead>
                <tbody>
                    {$table}
                </tbody>
            </table>
EOF;
        return null;
    }
}
