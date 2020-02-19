<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\{League, Provider};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DateTime;
use Exception;

class TransformKafkaMessage implements ShouldQueue
{
    use Dispatchable;

    protected $message;
    protected $swoole;

    public function __construct($message)
    {
        $this->message = json_decode($message);
    }


    public function handle()
    {
        //@TODO Transformation
        $swoole = app('swoole');
        $eventsTable = $swoole->eventsTable;
        $leaguesTable = $swoole->leaguesTable;
        $providersTable = $swoole->providersTable;
        $indexesTable = $swoole->indexesTable;

//        $leagueIndex = $indexesTable->exist('leagues') ? $indexesTable['leagues'] : 0;
//        $providerIndex = $indexesTable->exist('providers') ? $indexesTable['providers'] : 0;


        $schedule = new DateTime($this->message->data->referenceSchedule);
        $getDate = $schedule->format('Ymd');

//        $provider = Provider::where('alias', strtoupper($this->message->provider))->first();

//        $league = League::where('league', $this->message->data->leagueName)
//                    ->where('sport_id', $this->message->sportId)
//                    ->where('provider_id', $provider->id)
//                    ->first();

//        $uid = implode('-', [$getDate, $league->id, $this->message->data->events[0]->eventId_ft]);

        $providerSwtId = $this->message->provider;
        if ($providersTable->exist($providerSwtId)) {
            $providerId = $providersTable->get($this->message->provider)['id'];
        } else {
            throw new Exception("Provider doesn't exist");
        }

        $leagueSwtId = implode(':', [$this->message->sportId, $providerId, Str::slug($this->message->data->leagueName)]);
        if ($leaguesTable->exist($leagueSwtId)) {
            $leagueId = $leaguesTable->get($leagueSwtId)['id'];
        } else {
            $league = League::create([
                'sport_id'    => $this->message->sportId,
                'provider_id' => $providerId,
                'league'      => $this->message->data->leagueName
            ]);
            $leagueId = $league->id;

            $leaguesTable->set(implode(':', [$this->message->sportId, $providerId, Str::slug($this->message->data->leagueName)]),
                [
                    'id'          => $leagueId,
                    'sport_id'    => $this->message->sportId,
                    'provider_id' => $providerId,
                    'league'      => $this->message->data->leagueName
                ]
            );
        }

        $uid = implode('-', [$getDate, $leagueId, $this->message->data->events[0]->eventId_ft]);

var_dump($uid);



//        $eventsTable['1'] = ['uid' => $uid, 'timestamp' => 'asd', 'payload' => 'dsf'];
//        $eventsTable['2'] = ['uid' => $uid, 'timestamp' => 'asd', 'payload' => 'dsf'];
//        $a = wsEmit("SDfsdf");
//        Log::debug($a);
    }
}
