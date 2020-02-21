<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\{Events, League, Provider, Teams};
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

        /** DATABASE TABLES */
        /** LOOK-UP TABLES */
        $providersTable = $swoole->providersTable;
        $sportsTable    = $swoole->sportsTable;
        $leaguesTable   = $swoole->leaguesTable;
        $eventsTable    = $swoole->eventsTable;
        $teamsTable     = $swoole->teamsTable;
        $marketsTable   = $swoole->marketsTable;

        $schedule = new DateTime($this->message->data->referenceSchedule);
        $getDate = $schedule->format('Ymd');
        $providerSwtId = $this->message->provider;

        if ($providersTable->exist($providerSwtId)) {
            $providerId = $providersTable->get($this->message->provider)['id'];
        } else {
            throw new Exception("Provider doesn't exist");
        }

        /** SWT : Leagues */

        $leagueSwtId = implode(':', [
            $this->message->sportId,
            $providerId,
            Str::slug($this->message->data->leagueName)
        ]);

        if ($leaguesTable->exist($leagueSwtId)) {
            $leagueId = $leaguesTable->get($leagueSwtId)['id'];
        } else {
            /** Dispatch Job to do Database-related queries */
            $league = League::updateOrCreate(
                [
                    'sport_id'    => $this->message->sportId,
                    'provider_id' => $providerId,
                ],
                [
                    'league'      => $this->message->data->leagueName
                ]
            );
            $leagueId = $league->id;

            $leaguesTable->set($leagueSwtId,
                [
                    'id'           => $leagueId,
                    'sport_id'     => $this->message->sportId,
                    'provider_id'  => $providerId,
                    'multi_league' => $this->message->data->leagueName,
                    'timestamp'    => $this->message->request_ts
                ]
            );

            /** SWT : Teams */

            $teamSwtId = implode(':', [
                $this->message->sportId,
                $providerId,
                'teams',
                Str::slug($this->message->data->homeTeam),
                Str::slug($this->message->data->awayTeam),
            ]);

            $competitors = [
                'home' => $this->message->data->homeTeam,
                'away' => $this->message->data->awayTeam,
            ];

            if ($teamsTable->exists($teamSwtId)) {
                //
            } else {
                foreach ($competitors AS $key => $row) {
                    $teams = Teams::updateOrCreate(
                        [ 'provider_id' => $providerId ],
                        [ 'teams'       => $row ]
                    );

                    $teamsTable->set($teamSwtId,
                        [
                            'id'          => $teams->id,
                            'team_name'   => $row,
                            'provider_id' => $providerId
                        ]
                    );

                    $eventSwtId = implode(':', [
                        $this->message->sportId,
                        $providerId,
                        $this->message->data->events[0]->eventId_ft
                    ]);

                    if ($eventsTable->exists($eventSwtId)) {
                        $eventsId = $eventsTable->get($eventSwtId)['id'];
                    } else {
                        $events = Events::firstOrNew(
                            [
                                'event_identifier' => $this->message->data->events[0]->eventId_ft
                            ],
                            [
                                'league_id'        => $leagueId,
                            ]
                        );
                        $eventsId = $events->id;

                        $eventsTable->set($eventSwtId,
                            [
                                'id'               => $events->id,
                                'league_id'        => $leagueId,
                                'event_identifier' => $this->message->data->events[0]->eventId_ft
                            ]
                        );
                    }

                    DB::table('event_team_links')->updateOrCreate(
                        [
                            'team_id'   => $teams->id
                        ],
                        [
                            'event_id'  => $this->message->data->events[0]->eventId_ft,
                            'team_flag' => $key,
                        ]
                    );
                }
            }
        }

        $uid = implode('-', [
            $getDate,
            $leagueId,
            $this->message->data->events[0]->eventId_ft
        ]);

        /** `events` key from json data */
        $arrayEvents = $this->message->data->events;

        /** loop each `events` */
        foreach ($arrayEvents AS $event) {
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
        \Log::info(json_encode($arrayEvents));

        var_dump($uid);
    }
}