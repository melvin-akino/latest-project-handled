<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;
use Prometheus\Exception\MetricNotFoundException;
use Exception;
use Prometheus;

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

        try {

            $gauge = $exporter->getGauge('users_online_total');

        } catch (Exception $e) {

            $gauge = $exporter->registerGauge('users_online_total', 'The total number of users online.', ['group']);
        }

        $gauge->inc(['users']); // increment by 1

        $ttl = Redis::ttl("PROMETHEUS_:gauge:{$this->pnamespace}_users_online_total");

        if($ttl < 0){
            Redis::expire("PROMETHEUS_:gauge:{$this->pnamespace}_users_online_total",  env('PROMETHEUS_EXPIRE'));
        }

        return $next($request);
    }
}
