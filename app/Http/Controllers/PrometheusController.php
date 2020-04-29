<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\MetricFamilySamples;
//use Prometheus\Storage\Redis;
use Illuminate\Support\Facades\Redis;


class PrometheusController extends Controller
{
    public function index()
    {


        $results = Redis::smembers("PROMETHEUS_gauge_METRIC_KEYS");

        foreach ($results as $key => $value) {
            //echo $value . "::" . Redis::exists($value)  . "<br/>";
            if(!Redis::exists($value)){
                Redis::srem("PROMETHEUS_gauge_METRIC_KEYS", [$value]);
            }
        }


        $adapter = new \Prometheus\Storage\Redis();
        $registry = new CollectorRegistry($adapter);
        $renderer = new RenderTextFormat();


        $keys = Redis::smembers("PROMETHEUS_gauge_METRIC_KEYS");

        sort($keys);
        $gauges = array();
        foreach ($keys as $key) {
            //$raw = $this->redis->hGetAll($key);
            $raw = Redis::hgetall($key);
            $gauge = json_decode($raw['__meta'], true);
            unset($raw['__meta']);
            $gauge['samples'] = array();
            foreach ($raw as $k => $value) {
                $gauge['samples'][] = array(
                    'name' => $gauge['name'],
                    'labelNames' => array(),
                    'labelValues' => json_decode($k, true),
                    'value' => $value
                );
            }
            usort($gauge['samples'], function($a, $b){
                return strcmp(implode("", $a['labelValues']), implode("", $b['labelValues']));
            });
            $gauges[] = $gauge;
        }

        $test = array_map(
            function (array $metric) {
                return new MetricFamilySamples($metric);
            },
            $gauges
        );


        //$test = new MetricFamilySamples($gauges);

       
        //return;
        $result = $renderer->render($test);

        return response($result)
            ->header('Content-Type', RenderTextFormat::MIME_TYPE);

        //$adapter = new \Prometheus\Storage\Redis();
        //$registry = new CollectorRegistry($adapter);
        //$renderer = new RenderTextFormat();

        
        //$result = $renderer->render($registry->getMetricFamilySamples());
        //return response($result)
        //    ->header('Content-Type', RenderTextFormat::MIME_TYPE);
        
        //return ;
        
        }
}
