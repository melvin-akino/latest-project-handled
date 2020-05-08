<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\MetricFamilySamples;
//use Prometheus\Storage\Redis;
use Illuminate\Support\Facades\Redis;
use Prometheus;

class PrometheusController extends Controller
{

    public function index() {

        try {
            $results = Redis::smembers("PROMETHEUS_gauge_METRIC_KEYS");

            foreach ($results as $key => $value) {
                if(!Redis::exists($value)){
                    Redis::srem("PROMETHEUS_gauge_METRIC_KEYS", [$value]);
                }
            }
            $adapter = new \Prometheus\Storage\Redis();
            $registry = new CollectorRegistry($adapter);
            $renderer = new RenderTextFormat();

             $keys = Redis::smembers("PROMETHEUS_gauge_METRIC_KEYS");
             $gauges = array();

             foreach ($keys as $key) {
                //echo $key .'--<br>';
                 $raw = Redis::hgetall($key);
                 //var_dump($raw);
                 $gauge['samples'] = array();
                 if(array_key_exists('__meta', $raw)) {

                    $gauge = json_decode($raw['__meta'], true);
                    unset($raw['__meta']);
                    foreach ($raw as $k => $value) {
                        $gauge['samples'][] = array(
                            'name' => $gauge['name'],
                            'labelNames' => array(),
                            'labelValues' => json_decode($k, true),
                            'value' => $value
                        );
                    }
                    //print_r($gauge);

                    usort($gauge['samples'], function($a, $b){
                      //  return strcmp(implode("", $a['labelValues']), implode("", $b['labelValues']));
                        return $a['labelValues'] <=> $b['labelValues'];
                        //return implode("", $a['labelValues']) <=> implode("", $b['labelValues']);
                    });
                    $gauges[] = $gauge;

                }

            }
            $test = array_map(
                function (array $metric) {
                    return new MetricFamilySamples($metric);
                },
                $gauges
            );
            $result = $renderer->render($test);
            return response($result)
                ->header('Content-Type', RenderTextFormat::MIME_TYPE);
        } catch (\Exception $e) {
            echo $e->getMessage();
            echo  $e->getLine();
        }

    }
   
    
}
