<?php
namespace App\Matrics;
use Closure;
use Illuminate\Support\Facades\Redis;
use Prometheus\Exception\MetricNotFoundException;


use Prometheus;

class PrometheusMatric
{
	public $pnamespace;
    public function InitiateRequest($market_id)
    {
		$this->pnamespace = env('PROMETHEUS_NAMESPACE', 'default');
		$exporter = Prometheus::getFacadeRoot();
		try {

			$gauge = $exporter->getGauge('request_market_id');

		} catch (\Exception $e) {
			$gauge = $exporter->registerGauge('market_id_request_total', 'The total number of  market id  pushed.', ['group']);
		
		}
		$gauge->inc(["$market_id"]); // increment by 1


		$ttl = Redis::ttl("PROMETHEUS_:gauge:{$this->pnamespace}_market_id_request_total");

		if($ttl < 0){
		    Redis::expire("PROMETHEUS_:gauge:{$this->pnamespace}_market_id_request_total",  env('PROMETHEUS_EXPIRE'));
		}
    }

    public function InitiateResponse($market_id)
    {
		$this->pnamespace = env('PROMETHEUS_NAMESPACE', 'default');
		$exporter = Prometheus::getFacadeRoot();
		try {

			$gauge = $exporter->getGauge('response_market_id');

		} catch (\Exception $e) {
			$gauge = $exporter->registerGauge('market_id_response_total', 'The total number of  market id  response.', ['group']);
	
		}
		$gauge->inc(["$market_id"]); // increment by 1


		$ttl = Redis::ttl("PROMETHEUS_:gauge:{$this->pnamespace}_market_id_response_total");

		if($ttl < 0){
		    Redis::expire("PROMETHEUS_:gauge:{$this->pnamespace}_market_id_response_total",  env('PROMETHEUS_EXPIRE'));
		}

    }
}

?>