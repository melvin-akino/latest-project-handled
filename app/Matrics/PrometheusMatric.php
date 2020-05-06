<?php
namespace App\Matrics;
use Illuminate\Support\Facades\Redis;
use Prometheus\Exception\MetricNotFoundException;


use Prometheus;

class PrometheusMatric
{
	public $pnamespace;

	#example usage 
	# MakeMatrix("request_market_id_total","The total number of  market id  pushed.", $market_id)

	public function MakeMatrix($gaugename, $gauge_info, $gaugetoincrese)
	{
		$this->pnamespace = env('PROMETHEUS_NAMESPACE', 'default');

		$exporter = Prometheus::getFacadeRoot();

		try {

			$gauge = $exporter->getGauge($gaugename);

		} catch (\Exception $e) {
			$gauge = $exporter->registerGauge($gaugename, $gauge_info, ['group']);
		
		}
		$gauge->inc(["$gaugetoincrese"]); // increment by 1


		$ttl = Redis::ttl("PROMETHEUS_:gauge:{$this->pnamespace}_".$gaugename);

		if($ttl < 0){
		    Redis::expire("PROMETHEUS_:gauge:{$this->pnamespace}_".$gaugename,  env('PROMETHEUS_EXPIRE'));
		}
	}
    public function InitiateRequest($market_id)
    {
		$this->pnamespace = env('PROMETHEUS_NAMESPACE', 'default');
		$exporter = Prometheus::getFacadeRoot();
		try {

			$gauge = $exporter->getGauge('request_market_id_total');

		} catch (\Exception $e) {
			$gauge = $exporter->registerGauge('request_market_id_total', 'The total number of  market id  pushed.', ['group']);
		
		}
		$gauge->inc(["$market_id"]); // increment by 1


		$ttl = Redis::ttl("PROMETHEUS_:gauge:{$this->pnamespace}_request_market_id_total");

		if($ttl < 0){
		    Redis::expire("PROMETHEUS_:gauge:{$this->pnamespace}_request_market_id_total",  env('PROMETHEUS_EXPIRE'));
		}
    }

    public function InitiateResponse($market_id)
    {
		$this->pnamespace = env('PROMETHEUS_NAMESPACE', 'default');
		$exporter = Prometheus::getFacadeRoot();
		try {

			$gauge = $exporter->getGauge('response_market_id');

		} catch (\Exception $e) {
			$gauge = $exporter->registerGauge('response_market_id', 'The total number of  market id  response.', ['group']);
	
		}
		$gauge->inc(["$market_id"]); // increment by 1


		$ttl = Redis::ttl("PROMETHEUS_:gauge:{$this->pnamespace}_response_market_id_total");

		if($ttl < 0){
		    Redis::expire("PROMETHEUS_:gauge:{$this->pnamespace}_response_market_id_total",  env('PROMETHEUS_EXPIRE'));
		}

    }
}

?>