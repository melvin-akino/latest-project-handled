<?php

namespace App\Tasks;

use App\Models\{EventsData, League, MasterLeague, MasterTeam, Team};
use Hhxsv5\LaravelS\Swoole\Task\Task;

class TransformKafkaMessageEventData extends Task
{
    protected $message;
    protected $internalParameters;

    CONST PRIORITY_HG_PROVIDER = 1;

    public function __construct($message, $internalParameters)
    {
        $this->message            = $message;
        $this->internalParameters = $internalParameters;
    }

    public function handle()
    {
        list('sportId' => $sportId, 'providerId' => $providerId) = $this->internalParameters;

        $eventData = EventsData::where('sport_id', $sportId)
                               ->where('provider_id', $providerId)
                               ->where('league_name', $this->message->data->leagueName)
                               ->where('home_team_name', $this->message->data->homeTeam)
                               ->where('away_team_name', $this->message->data->awayTeam)
                               ->where('ref_schedule', date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)))
                               ->where('game_schedule', $this->message->data->schedule)
                               ->where('event_identifier', $this->message->data->events[0]->eventId)
                               ->first();

        if (!$eventData) {
            EventsData::create([
                'sport_id'         => $sportId,
                'provider_id'      => $providerId,
                'league_name'      => $this->message->data->leagueName,
                'home_team_name'   => $this->message->data->homeTeam,
                'away_team_name'   => $this->message->data->awayTeam,
                'ref_schedule'     => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                'game_schedule'    => $this->message->data->schedule,
                'event_identifier' => $this->message->data->events[0]->eventId,
                'is_matched' => false
            ], []);
        }

        $swoole = app('swoole');

        $doesExist = false;
        foreach ($swoole->eventsTable as $key => $event) {
            if ($event['event_identifier'] == $this->message->data->events[0]->eventId) {
                $doesExist = true;
                break;
            }
        }

        if ($doesExist) {
            $event = EventsData::where('event_identifier', $this->message->data->events[0]->eventId)->first();
            if ($event) {
                $event->is_matched = true;
                $event->save();
            }
        } else if ($providerId == self::PRIORITY_HG_PROVIDER) {
            $leagueName = $this->message->data->leagueName;
            $team1 = $this->message->data->homeTeam;
            $team2 = $this->message->data->awayTeam;

            $masterLeague = MasterLeague::withTrashed()->updateOrCreate([
                'name' => $leagueName,
                'sport_id'           => $sportId
            ], [
                'deleted_at' => null
            ]);

            $matchedLeagueId = $masterLeague->id;

            League::withTrashed()->updateOrCreate([
                'master_league_id' => $matchedLeagueId,
                'sport_id'         => $sportId,
                'provider_id'      => $providerId,
                'name'             => $leagueName
            ], [
                'deleted_at' => null
            ]);

            $masterTeam1 = MasterTeam::withTrashed()->updateOrCreate([
                'sport_id' => $sportId,
                'name'     => $team1,
            ], [
                'deleted_at' => null
            ]);

            $matchedHomeTeamId = $masterTeam1->id;

            Team::withTrashed()->updateOrCreate([
                'sport_id'       => $sportId,
                'name'           => $team1,
                'provider_id'    => $providerId,
                'master_team_id' => $matchedHomeTeamId
            ], [
                'deleted_at' => null
            ]);

            $masterTeam2 = MasterTeam::withTrashed()->updateOrCreate([
                'sport_id' => $sportId,
                'name'     => $team2,
            ], [
                'deleted_at' => null
            ]);

            $matchedAwayTeamId = $masterTeam2->id;

            Team::withTrashed()->updateOrCreate([
                'sport_id'       => $sportId,
                'name'           => $team2,
                'provider_id'    => $providerId,
                'master_team_id' => $matchedAwayTeamId
            ], [
                'deleted_at' => null
            ]);
        }
    }
}
