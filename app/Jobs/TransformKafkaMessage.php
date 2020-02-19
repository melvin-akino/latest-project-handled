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
        $swoole         = app('swoole');
        $indexesTable   = $swoole->indexesTable;
        $providersTable = $swoole->providersTable;
        $leaguesTable   = $swoole->leaguesTable;
        $eventsTable    = $swoole->eventsTable;
        $teamsTable     = $swoole->teamsTable;
        $marketsTable   = $swoole->marketsTable;

        // $leagueIndex = $indexesTable->exist('leagues') ? $indexesTable['leagues'] : 0;
        // $providerIndex = $indexesTable->exist('providers') ? $indexesTable['providers'] : 0;

        $schedule = new DateTime($this->message->data->referenceSchedule);
        $getDate = $schedule->format('Ymd');

        // $provider = Provider::where('alias', strtoupper($this->message->provider))->first();

        // $league = League::where('league', $this->message->data->leagueName)
        //     ->where('sport_id', $this->message->sportId)
        //     ->where('provider_id', $provider->id)
        //     ->first();

        // $uid = implode('-', [$getDate, $league->id, $this->message->data->events[0]->eventId_ft]);

        $providerSwtId = $this->message->provider;

        if ($providersTable->exist($providerSwtId)) {
            $providerId = $providersTable->get($this->message->provider)['id'];
        } else {
            throw new Exception("Provider doesn't exist");
        }

        /** SWT : Leagues */

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
                    'id'           => $leagueId,
                    'sport_id'     => $this->message->sportId,
                    'provider_id'  => $providerId,
                    'multi_league' => $this->message->data->leagueName
                ]
            );

            /** SWT : Teams */

            $teamSwtId = implode(':', []);

            if ($teamsTable->exists($teamSwtId)) {

            } else {
                /** TODO: Insert to Teams Database Table */

                // $teamsTable->set($teamSwtId,
                //     [

                //     ]
                // );
            }
        }

        $uid = implode('-', [$getDate, $leagueId, $this->message->data->events[0]->eventId_ft]);

        /** SWT : Events */

        $eventSwtId = implode(':',
            [
                /** sport_id */
                /** provider_id */
                /** game_schedule */
            ]
        );

        /** TODO: must complete condition */
        if ($eventsTable->exists($eventSwtId)) {
            \Log::info(json_encode([ 'events' => $eventsTable->get($eventSwtId) ]));
        } else {
            /** TODO: Insert to Events Database Table */

            /** `events` key from json data */
            $array = $this->message->data->events;

            /** loop each `events` */
            foreach ($array AS $event) {
                /** object keys to be removed from each `events` */
                $disregardEvents = [ 'gnum_h', 'gnum_c', 'odd_since', ];

                foreach ($disregardEvents AS $row) {
                    /** remove $disregardEvents keys */
                    unset($event->{$row});

                    /** loop each `market_odds` inside every `events` */
                    foreach ($event->market_odds AS $columns) {
                        /** loop each `marketSelection` from each `market_odds` */
                        foreach ($columns->marketSelection AS $markets) {
                            /** object keys to be removed from each `marketSelections` */
                            $disregardMarkets = [ 'type', 'rtype', 'wtype' ];

                            foreach ($disregardMarkets AS $_row) {
                                /** remove $disregardMarkets keys */
                                unset($markets->{$_row});
                            }

                            /** Fill `markets` swoole table */
                            /** SWT : Markets */

                            $marketSwtId = implode(':', []);

                            if ($marketsTable->exists($marketSwtId)) {

                            } else {
                                /** TODO: Insert to Markets Database Table */

                                // $marketsTable->set($marketSwtId,
                                //     [
                                //         'id'           => "",
                                //         'uid'          => "",
                                //         'sport_id'     => "",
                                //         'sport_odd_id' => "",
                                //         'odds'         => "",
                                //         'odd_label'    => "",
                                //         'bet_id'       => "",
                                //         'market_type'  => "",
                                //         'team_flag'    => "",
                                //     ]
                                // );
                            }
                        }
                    }
                }
            }

            /** Logged complete `payload` to save to `events` swoole table */
            \Log::info(json_encode($array));

            $eventsTable->set($eventSwtId,
                [
                    // 'uid'       => $uid, /** which one is correct? */
                    'uid'       => $this->message->request_uid, /** which one is correct? */
                    'timestamp' => $this->message->request_ts,
                    'payload'   => json_encode($array),
                ]
            );
        }

        var_dump($uid);

        // $eventsTable['1'] = ['uid' => $uid, 'timestamp' => 'asd', 'payload' => 'dsf'];
        // $eventsTable['2'] = ['uid' => $uid, 'timestamp' => 'asd', 'payload' => 'dsf'];
        // $a = wsEmit("SDfsdf");
        // Log::debug($a);
    }
}