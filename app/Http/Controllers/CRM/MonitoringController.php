<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Exception;

class MonitoringController extends Controller
{

    public function odds() 
    {

        try {
            $jsonResult = [];
            $results = Redis::smembers("REDIS-SCRAPING-ODDS");
            foreach ($results as $value) {
                if(Redis::exists($value)) {
                    $previous = Redis::hget($value,'previous');
                    $latest = Redis::hget($value,'latest');
                    //$jsonResult[] = [json_decode($previous), json_decode($latest)];
                    $previousJSON = json_decode($previous, true);
                    $latestJSON = json_decode($latest, true);
                    //Lets construct the human-readable array here

                    foreach($previousJSON as $k => $v) {
                        $jsonResult = [
                            'league'    => $v['leagueName'],
                            'home'      => $v['homeTeam'],
                            'away'      => $v['awayTeam']
                        ];
                    }

                    dd($jsonResult);
                    break;
                }
                
            }

            dd($jsonResult);

            $result = $renderer->render($test);
            return response($result)
                ->header('Content-Type', RenderTextFormat::MIME_TYPE);
        } catch (Exception $e) {
            echo $e->getMessage();
            echo  $e->getLine();
        }
    }
}
