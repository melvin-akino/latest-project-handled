<?php

namespace App\Http\Controllers\CRM\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Exception;

class MonitoringToolController extends Controller
{
    //
    

    public function minmax() 
    {
    	$markets = [];
    	$redisTopic = env('REDIS_TOOL_MINMAX', 'REDIS-MON-TOOL-MINMAX-ODDS');
    	$smembers = Redis::smembers($redisTopic);

    	if (count($smembers) != 0) {
	    	foreach ($smembers as $k => $v) {
	    		$latest = Redis::hget($v,'latest');
	    		$prev = Redis::hget($v,'previous');
	    		$markets[$v] = ['previous' => ($prev), 'latest' => ($latest)];
	    	}
    	}

    	$data['page_title']       	= "Monitoring Tool";
        $data['page_description'] 	= 'Minmax Monitoring Tools';
    	$data['minmaxs'] 			=  $markets;
    	$data['monitoring_menu']    = true;
    	$data['logs_menu']			= false;
    	$data['minmax_menu'] 		= true;
    	$data['placebet_menu'] 		= false;
 
    	return view('CRM.monitoring.minmax')->with($data);
    }

    public function placedBet()
    {
    	$markets 	= [];
    	$redisTopic = env('REDIS_TOOL_PLACED_BET', 'REDIS-MON-TOOL-PLACED-BET');
    	$smembers 	= Redis::smembers($redisTopic);

    	if (count($smembers) != 0) {
	    	foreach ($smembers as $k => $v) {
	    		$latest = Redis::hget($v,'latest');
	    		$prev 	= Redis::hget($v,'previous');
	    		$markets[$v] = ['previous' => ($prev), 'latest' => ($latest)];
	    	}
    	}

    	$data['page_title']       	= "Monitoring Tool";
        $data['page_description'] 	= 'Placed Bet Monitoring Tools';
    	$data['minmaxs'] 			=  $markets;
    	$data['monitoring_menu']    = true;
    	$data['logs_menu']			= false;
    	$data['minmax_menu'] 		= false;
    	$data['placebet_menu'] 		= true;
    	return view('CRM.monitoring.placedbet')->with($data);


    }

    public function odds() 
    {

        try {
            $data = [
                'page_title'       => "Odds Monitoring",
                'page_description' => "Lists all scraped odds from providers",
                'monitoring_menu'   => true,
                'logs_menu'			=> true,
                'minmax_menu'		=> false,
                'placebet_menu'		=> false,
            ];
            $results = Redis::smembers("REDIS-SCRAPING-ODDS");
            foreach ($results as $value) {
                if(Redis::exists($value)) {
                    $previous = Redis::hget($value, 'previous');
                    $latest = Redis::hget($value, 'latest');
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
