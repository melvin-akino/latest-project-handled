<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DateTime;
use Exception;

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
        $this->message = json_decode($message->payload);
    }

    public function handle()
    {
        $swoole = app('swoole');

        if (empty($this->message)) {
            return;
        }

        $providersTable = $swoole->providersTable;
        $wsTable        = $swoole->wsTable;
        $sportsTable    = $swoole->sportsTable;
        $leaguesTable   = $swoole->leaguesTable;
        $eventsTable    = $swoole->eventsTable;

        $providerSwtId = "providerAlias:" . strtolower($this->message->data->provider);

        if ($providersTable->exist($providerSwtId)) {
            $providerId = $providersTable->get($providerSwtId)['id'];
        } else {
            throw new Exception("Provider doesn't exist");
        }

        $sportSwtId = "sId:" . $this->message->data->sport;

        if ($sportsTable->exists($sportSwtId)) {
            $sportId = $sportsTable->get($sportSwtId)['id'];
            $sportName = $sportsTable->get($sportSwtId)['sport'];
        } else {
            throw new Exception("Sport doesn't exist");
        }

        $consumeLeagueSwtId = implode(':', [
            "pId:" . $providerId,                    /** PROVIDER ID */
            "sId:" . $sportId,                       /** SPORT ID */
            "gs:"  . $this->message->data->schedule, /** GAME SCHEDULE */
        ]);

        $timestampSwtId = $consumeLeagueSwtId . "::KAFKA_CONSUME_LEAGUES";

        if ($wsTable->exists($timestampSwtId)) {
            $swooleTS = $wsTable[$timestampSwtId]['value'];

            if ($swooleTS > $this->message->request_ts) {
                return;
            }
        }

        $leagueList = array_filter(
            array_map(function ($league) {
                if ($this->filterLeague($league)) {
                    return $league;
                }
            }, $this->message->data->leagues)
        );

        $wsLeagueTableSwtId = $consumeLeagueSwtId . "::STORE_CONSUMED_LEAGUES";
        $diff = [
            'rmv' => [],
            'add' => [],
        ];

        if ($wsTable->exists($wsLeagueTableSwtId)) {
            if (md5($wsTable[$wsLeagueTableSwtId]['value']) != md5(json_encode($leagueList))) {
                $swooleValue = json_decode($wsTable[$wsLeagueTableSwtId]['value'], true);
                $diff['rmv'] = array_diff($swooleValue, $leagueList);
                $diff['add'] = array_diff($leagueList, $swooleValue);

                $wsTable[$wsLeagueTableSwtId]['value'] = json_encode($leagueList);
            }
        } else {
            $wsTable->set($wsLeagueTableSwtId, [ 'value' => json_encode($leagueList) ]);
        }

        foreach ($diff AS $key => $_diff) {
            if (!empty($_diff)) {
                foreach ($_diff AS $actionLeague) {
                    $leagueLookupId = null;

                    foreach ($wsTable as $_key => $value) {
                        if (strpos($_key, 'leagueLookUpId:') === 0) {
                            if ($value['value'] == $actionLeague) {
                                $leagueLookupId = substr($_key, strlen('leagueLookUpId:'));
                            }
                        }
                    }

                    $leagueSwtId = implode(':', [
                        "sId:"            . $sportId,
                        "pId:"            . $providerId,
                        "leagueLookUpId:" . $leagueLookupId
                    ]);

                    if ($leaguesTable->exists($leagueSwtId)) {
                        $multiLeagueId    = $leaguesTable->get($leagueSwtId)['id'];
                        $masterLeagueName = $leaguesTable->get($leagueSwtId)['master_league_name'];
                    } else {
                        break;
                    }

                    $ctr = 0;

                    foreach ($eventsTable AS $eventKey => $event) {
                        if (strpos($eventKey, 'sId:' . $sportId . ':pId: ' . $providerId . ':eventIdentifier:') == 0) {
                            if ($eventsTable[$eventKey]['master_league_name'] == $masterLeagueName) {
                                $ctr++;
                            }
                        }
                    }

                    $preData = [
                        'schedule'    => strtolower($this->message->data->schedule),
                        'name' => $masterLeagueName,
                    ];

                    if ($key == 'add') {
                        $preData['match_count'] = $ctr;
                    }

                    $data[$key][] = $preData;
                }

                $action   = $key == "rmv" ? "LEAGUE_REMOVAL" : "LEAGUE_ADDITIONAL";
                $swooleId = implode(':', [
                    "pId:" . $providerId,
                    "sId:" . $sportId,
                    ":"    . $action
                ]);

                if (!$wsTable->exists($swooleId)) {
                    $wsTable->set($swooleId, [ 'value' => json_encode($data) ]);
                }
            }
        }

        echo "END ITERATION\n";
    }

    /**
     * League Name Filteration
     *
     * @return boolean
     */
    private function filterLeague(string $leagueName)
    {
        $i = 0;

        foreach ($this->disregard AS $row) {
            if (strpos($leagueName, $row) > -1) {
                $i++;
            }
        }

        return $i == 0 ? true : false;
    }
}
