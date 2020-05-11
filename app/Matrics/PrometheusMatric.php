<?php
namespace App\Matrics;

use Illuminate\Support\Facades\Redis;
use Prometheus\Exception\MetricNotFoundException;
use Prometheus;
use Exception;

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

		} catch (Exception $e) {
			$gauge = $exporter->registerGauge($gaugename, $gauge_info, ['group']);
		
		}
		$gauge->inc(["$gaugetoincrese"]); // increment by 1

		$ttl = Redis::ttl("PROMETHEUS_:gauge:{$this->pnamespace}_".$gaugename);

		if($ttl < 0 ){
		    Redis::expire("PROMETHEUS_:gauge:{$this->pnamespace}_".$gaugename,  env('PROMETHEUS_EXPIRE'));
		}
	}
}

?>