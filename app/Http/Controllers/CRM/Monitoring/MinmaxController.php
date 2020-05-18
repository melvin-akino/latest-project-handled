<?php

namespace App\Http\Controllers\CRM\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class MinmaxController extends Controller
{
    //

    public function index()
    {
    	$markets = [];
    	$redisTopic = env('REDIS_TOOL_MINMAX', 'REDIS-MON-TOOL-MINMAX-ODDS');
    	$smembers = Redis::smembers($redisTopic);

    	if (count($smembers) != 0) {
	    	foreach ($smembers as $k => $v) {
	    		$latest = Redis::hget($v,'latest');
	    		$prev = Redis::hget($v,'previous');
	    		//$latest = $latest->payload;
	    		//$prev = $prev->payload;
	    		$markets[$v] = ['previous' => ($prev), 'latest' => ($latest)];
	    	}
    	}

    	$data['page_title']       = "Monitoring Tool";
        $data['monitoring_menu']    = true;
        $data['page_description'] = 'Minmax Monitoring Tools';
    	$data['minmaxs'] =  $markets;
    	return view('CRM.monitoring.minmax')->with($data);
    }
}
