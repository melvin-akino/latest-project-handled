<?php

$conf = new RdKafka\Conf();
$conf->set('group.id', 'ml');
$conf->set('metadata.broker.list', 'kafka:9092');
$conf->set('auto.offset.reset', 'latest');
$conf->set('enable.auto.commit', 'false');

$rk = new RdKafka\Producer($conf);
$topic = $rk->newTopic("SCRAPING-PROVIDER-EVENTS");

while(true) {
	try {
		echo '.';
		$topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData()));
		usleep(1000000);
		echo '.';
		$topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData2()));
		usleep(1000000);
		echo '.';
		$topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData3()));
		usleep(1000000);
	} catch (\Exception $e) {
		echo '!';
	}
}

function testData()
{
	$permittedChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  	return array (
		'request_uid' => str_shuffle($permittedChars),
		'request_ts' => milliseconds(),
		'command' => 'event',
		'sub_command' => 'transform',
		'data' => [
			'provider' => 'hg',
		    'schedule' => 'early',
		    'sport' => 1,
			'event_ids' => ['1234567']
		]
		
    );
}

function testData2()
{
	$permittedChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  	return array (
		'request_uid' => str_shuffle($permittedChars),
		'request_ts' => milliseconds(),
		'command' => 'event',
		'sub_command' => 'transform',
		'data' => [
			'provider' => 'hg',
		    'schedule' => 'today',
		    'sport' => 1,
			'event_ids' => ['1234560', '1234561', '1234562']
		]
		
    );
}

function testData3()
{
	$permittedChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  	return array (
		'request_uid' => str_shuffle($permittedChars),
		'request_ts' => milliseconds(),
		'command' => 'event',
		'sub_command' => 'transform',
		'data' => [
			'provider' => 'hg',
		    'schedule' => 'inplay',
		    'sport' => 1,
			'event_ids' => ['1234563', '1234564', '1234565', '1234567']
		]
		
    );
}

function milliseconds()
{
	$mt = explode(' ', microtime());
	return bcadd($mt[1], $mt[0], 8);
}

