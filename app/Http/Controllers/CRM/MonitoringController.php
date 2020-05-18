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
            $data = [
                'page_title'       => "Odds Monitoring",
                'page_description' => "Lists all scraped odds from providers",
                'dashboard_menu'   => true,
            ];
            $results = Redis::smembers("REDIS-SCRAPING-ODDS");
            foreach ($results as $value) {
                if(Redis::exists($value)) {
                    $previous = Redis::hget($value,'previous');
                    $latest = Redis::hget($value,'latest');
                    //$jsonResult[] = [json_decode($previous), json_decode($latest)];
                    $previousJSON = json_decode($previous,true);
                    $latestJSON = json_decode($latest,true);
                    //Lets construct the human-readable array here

                    foreach($previousJSON as $k => $v) {
                        if ($k == 'data') {
                            $data['odds'][] = [
                                'league'    => $v['leagueName'],
                                'home'      => $v['homeTeam'],
                                'away'      => $v['awayTeam'],
                                'schedule'  => $v['schedule'],
                                'latest'    => $latestJSON,
                                'previous'  => $previousJSON
                            ];

                            break;    
                        }                       
                    }
                }
                
            }

            return view('CRM.monitoring.odds')
            ->with($data);
        } catch (Exception $e) {
            echo $e->getMessage();
            echo  $e->getLine();
        }
    }
}
