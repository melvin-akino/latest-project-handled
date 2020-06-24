<?php

namespace App\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class TransformKafkaMessageLeagues implements ShouldQueue
{
    use Dispatchable;

    protected $message;
    protected $swoole;
    protected $disregard = [
        'No. of Corners',
        'No. of Bookings',
        'Extra Time',
        'To Qualify',
        'Winner',
        'PK(Handicap)',
        'PK(Over/Under)',
        'games (e.g',
        'Days (',
        ' Game',
        'Corners',
        'borders',
        'To Win Final',
        'To Finish 3rd',
        'To Advance',
        '(w)',
        '(n)',
        'Home Team',
        'Away Team',
        'To Win',
        'TEST'
    ];

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function handle()
    {
        try {
            $swoole = app('swoole');

            $providersTable        = $swoole->providersTable;
            $sportsTable           = $swoole->sportsTable;
            $leaguesTable          = $swoole->leaguesTable;
            $eventsTable           = $swoole->eventsTable;
            $getActionLeaguesTable = $swoole->getActionLeaguesTable;
            $consumeLeaguesTable   = $swoole->consumeLeaguesTable;

            $providerSwtId = "providerAlias:" . strtolower($this->message->data->provider);

            $doesExist = false;
            foreach ($providersTable as $key => $value) {
                if ($key == $providerSwtId) {
                    $doesExist = true;
                    break;
                }
            }

            if ($doesExist) {
                $providerId = $providersTable->get($providerSwtId)['id'];
            } else {
                Log::info("League Transformation ignored - Provider doesn't exist");
                return;
            }

            $sportSwtId = "sId:" . $this->message->data->sport;

            $doesExist = false;
            foreach ($sportsTable as $key => $value) {
                if ($key == $sportSwtId) {
                    $doesExist = true;
                    break;
                }
            }
            if ($doesExist) {
                $sportId = $sportsTable->get($sportSwtId)['id'];
            } else {
                Log::info("League Transformation ignored - Sport doesn't exist");
                return;
            }

            $doesExist     = false;
            $swtRequestUID = null;
            foreach ($swoole->scraperRequestsTable as $key => $scraperRequestsTable) {
                if ($key == 'type:leagues:requestUID:' . $this->message->request_uid) {
                    $swtRequestUID = $this->message->request_uid;
                    $doesExist     = true;
                }
            }
            if (!$doesExist) {
                Log::info("League Transformation ignored - Request UID is from ML");
                return;
            }

            $consumeLeaguesTablewtId = implode(':', [
                "pId:" . $providerId,
                /** PROVIDER ID */
                "sId:" . $sportId,
                /** SPORT ID */
                "gs:" . $this->message->data->schedule,
                /** GAME SCHEDULE */
            ]);

            $timestampSwtId = $consumeLeaguesTablewtId . "::KAFKA_CONSUME_LEAGUES";

            $doesExist = false;
            foreach ($consumeLeaguesTable as $key => $value) {
                if ($key == $timestampSwtId) {
                    $doesExist = true;
                    break;
                }
            }
            if ($doesExist) {
                $swooleTS = $consumeLeaguesTable[$timestampSwtId]['value'];

                if ($swooleTS > $this->message->request_ts) {
                    Log::info("League Transformation ignored - Old Timestamp");
                    return;
                }
            }

            $leagueList = array_filter(
                array_map(function ($league) {
                    if ($this->filterLeague($league)) {
                        Log::info("League Transformation ignored - Filtered League");
                        return;
                    }
                }, $this->message->data->leagues)
            );

            $wsLeagueTableSwtId = $consumeLeaguesTablewtId . "::STORE_CONSUMED_LEAGUES";
            $diff               = [
                'rmv' => [],
                'add' => [],
            ];

            $doesExist = false;
            foreach ($consumeLeaguesTable as $key => $value) {
                if ($key == $wsLeagueTableSwtId) {
                    $doesExist = true;
                    break;
                }
            }
            if ($doesExist) {
                if (md5($consumeLeaguesTable[$wsLeagueTableSwtId]['value']) != md5(json_encode($leagueList))) {
                    $swooleValue = json_decode($consumeLeaguesTable[$wsLeagueTableSwtId]['value'], true);
                    $diff['rmv'] = array_diff($swooleValue, $leagueList);
                    $diff['add'] = array_diff($leagueList, $swooleValue);

                    $consumeLeaguesTable[$wsLeagueTableSwtId]['value'] = json_encode($leagueList);
                } else {
                    Log::info("League Transformation ignored - No Change");
                    return;
                }
            } else {
                $consumeLeaguesTable->set($wsLeagueTableSwtId, ['value' => json_encode($leagueList)]);
            }

            foreach ($diff as $key => $_diff) {
                if (!empty($_diff)) {
                    foreach ($_diff as $actionLeague) {
                        // $leagueLookupId = null;

                        // foreach ($leagueLookUpTable as $_key => $value) {
                        //     if ($value['value'] == $actionLeague) {
                        //         $leagueLookupId = substr($_key, strlen('leagueLookUpId:'));
                        //     }
                        // }

                        // $leagueSwtId = implode(':', [
                        //     "sId:" . $sportId,
                        //     "pId:" . $providerId,
                        //     "leagueLookUpId:" . $leagueLookupId
                        // ]);

                        $doesExist = false;
                        foreach ($leaguesTable as $k => $v) {
                            if ($v['league_name'] == $actionLeague) {
                                $doesExist = true;
                                break;
                            }
                        }
                        if ($doesExist) {
                            $masterLeagueName = $leaguesTable->get($leagueSwtId)['master_league_name'];
                            $masterLeagueId   = $leaguesTable->get($leagueSwtId)['id'];
                        } else {
                            break;
                        }

                        $ctr = 0;

                        foreach ($eventsTable as $eventKey => $event) {
                            if (strpos($eventKey,
                                    'sId:' . $sportId . ':pId: ' . $providerId . ':eventIdentifier:') == 0) {
                                if ($eventsTable[$eventKey]['master_league_id'] == $masterLeagueId) {
                                    $ctr++;
                                }
                            }
                        }

                        $preData = [
                            'schedule' => strtolower($this->message->data->schedule),
                            'name'     => $masterLeagueName,
                        ];

                        if ($key == 'add') {
                            $preData['match_count'] = $ctr;
                        }

                        $data[$key][] = $preData;
                    }

                    if ($key == 'rmv') {
                        TransformationLeagueRemoval::dispatch($data[$key], $sportId);
                    }

                    $action   = $key == "rmv" ? "LEAGUE_REMOVAL" : "LEAGUE_ADDITIONAL";
                    $swooleId = implode(':', [
                        "pId:" . $providerId,
                        "sId:" . $sportId,
                        ":" . $action
                    ]);

                    $doesExist = false;
                    foreach ($getActionLeaguesTable as $k => $v) {
                        if ($k == $swooleId) {
                            $doesExist = true;
                            break;
                        }
                    }
                    if (!$doesExist) {
                        $getActionLeaguesTable->set($swooleId, ['value' => json_encode($data)]);
                    }
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

    }

    /**
     * League Name Filteration
     *
     * @return boolean
     */
    private function filterLeague(string $leagueName)
    {
        $i = 0;

        foreach ($this->disregard as $row) {
            if (strpos($leagueName, $row) > -1) {
                $i++;
            }
        }

        return $i == 0 ? true : false;
    }
}
