<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;
use Prometheus\Exception\MetricNotFoundException;
use Prometheus;
use Exception;

class PrometheusUrlLog
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
      
        $this->pnamespace = env('PROMETHEUS_NAMESPACE', 'default');
        $uri = str_replace("/","_",$request->getPathInfo());

        $executionTime = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];

        $exporter = Prometheus::getFacadeRoot();

        try {
            $gauge = $exporter->getGauge('urls');

        } catch(Exception $e) {
        // create a gauge (with labels)
            $gauge = $exporter->registerGauge('urls', 'Url access', ['url']);
        }

        $gauge->inc(["{$uri}"]); // increment by 1

        $ttl = Redis::ttl("PROMETHEUS_:gauge:{$this->pnamespace}_urls");

        if($ttl < 0){
            Redis::expire("PROMETHEUS_:gauge:{$this->pnamespace}_urls", env('PROMETHEUS_EXPIRE')); 
        }
    }
}
