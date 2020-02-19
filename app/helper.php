<?php

use Illuminate\Support\Facades\Cookie;
use RdKafka\Conf as KafkaConf;

/**
 * Delete Cookie by Name
 *
 * @param  string   $cookieName     Illuminate\Support\Facades\Cookie;
 */
function deleteCookie(string $cookieName)
{
    Cookie::queue(Cookie::forget($cookieName));
}

/**
 * Kafka default configuration
 *
 * return KafkaConf $config
 */
if (!function_exists('kafkaConfig')) {
    function kafkaConfig(): KafkaConf
    {
        $conf = new KafkaConf();
        $conf->set('group.id', 'multiline');
        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));
        $conf->set('auto.offset.reset', 'smallest');
        $conf->set('enable.auto.commit', 'false');
        return $conf;
    }
}
