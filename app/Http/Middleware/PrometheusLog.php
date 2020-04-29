<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

//use Monolog\Processor\GitProcessor;
//use Monolog\Processor\WebProcessor;
//use Monolog\Processor\MemoryUsageProcessor;

use Prometheus;
//use Ucc\Redislog\Services\Redislog;

class PrometheusLog
{



    public $pnamespace;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

       $this->pnamespace = env('PROMETHEUS_NAMESPACE', 'default');
 
       $exporter = Prometheus::getFacadeRoot();


        
        


       $gauge = $exporter->registerGauge('users_online_total', 'The total number of users online.', ['group']);
       $gauge->inc(['users']); // increment by 1
  

       $ttl = Redis::ttl("PROMETHEUS_:gauge:{$this->pnamespace}_users_online_total");

       if($ttl < 0){
            Redis::expire("PROMETHEUS_:gauge:{$this->pnamespace}_users_online_total",  env('PROMETHEUS_EXPIRE'));
       }
       

       return $next($request);
    }

    public function terminate($request, $response)
    {
     
        $this->pnamespace = env('PROMETHEUS_NAMESPACE', 'default');
        $uri = str_replace("/","_",$request->getPathInfo());
        $executionTime = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];

        $exporter = Prometheus::getFacadeRoot();
        // create a gauge (with labels)
        $gauge = $exporter->registerGauge('urls', 'Url access', ['url']);
        $gauge->inc(["{$uri}"]); // increment by 1


        $ttl = Redis::ttl("PROMETHEUS_:gauge:{$this->pnamespace}_urls");

        if($ttl < 0){
            Redis::expire("PROMETHEUS_:gauge:{$this->pnamespace}_urls", env('PROMETHEUS_EXPIRE')); 
        }

        
        
    }
}
