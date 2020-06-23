<?php

namespace App\Tasks;

use App\Models\EventsData;
use App\Models\League;
use App\Models\Team;
use Hhxsv5\LaravelS\Swoole\Task\Task;

class TransformKafkaMessageEventData extends Task
{
    protected $message;
    protected $internalParameters;

    public function init($message, $internalParameters)
    {
        $this->message            = $message;
        $this->internalParameters = $internalParameters;
        return $this;
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
                'is_matched'       => false
            ]);
        }

        $leagueName = $this->message->data->leagueName;
        $team1      = $this->message->data->homeTeam;
        $team2      = $this->message->data->awayTeam;

        League::withTrashed()->updateOrCreate([
            'sport_id'    => $sportId,
            'provider_id' => $providerId,
            'name'        => $leagueName
        ], []);

        Team::withTrashed()->updateOrCreate([
            'sport_id'    => $sportId,
            'name'        => $team1,
            'provider_id' => $providerId,
        ], []);


        Team::withTrashed()->updateOrCreate([
            'sport_id'    => $sportId,
            'name'        => $team2,
            'provider_id' => $providerId
        ], []);
    }
}
