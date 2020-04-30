<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Redis;
use Prometheus\Exception\MetricNotFoundException;

use Prometheus;
//use Ucc\Redislo

use Closure;

class PrometheusBetLog
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
      try {
       
       $gauge = $exporter->getGauge('user_bet');
       
      } catch (\Exception $e) {
        $gauge = $exporter->registerGauge('user_bet_total', 'The total number of users bet.', ['group']);
        //echo $e->getMessage();
      }
       $gauge->inc(['bet']); // increment by 1
  

       $ttl = Redis::ttl("PROMETHEUS_:gauge:{$this->pnamespace}_user_bet_total");

       if($ttl < 0){
            Redis::expire("PROMETHEUS_:gauge:{$this->pnamespace}_user_bet_total",  env('PROMETHEUS_EXPIRE'));
       }
        return $next($request);
    }
}
