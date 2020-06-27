<?php

$conf = new RdKafka\Conf();
$conf->set('group.id', 'multiline');
$conf->set('metadata.broker.list', 'kafka:9092');
$conf->set('auto.offset.reset', 'latest');
$conf->set('enable.auto.commit', 'false');

$rk = new RdKafka\Producer($conf);
$topic = $rk->newTopic("MINMAX-ODDS");

//testData("", "Union La Calera", "Coquimbo", "hg", 1, 'early', 1234567, 2345678, "2020-03-18T01:00:00.000+04:00")

while(true) {
  try {
    echo '.';
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('1X2H1234567', 'hg')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('1X2A1234567', 'hg')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('1X2D1234567', 'hg')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('HDPH1234567', 'hg')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('HDPA1234567', 'hg')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('OUO1234567', 'hg')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('OUUH1234567', 'hg')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('H1X2H1234567', 'hg')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('H1X2A1234567', 'hg')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('H1X2D1234567', 'hg')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('HHDPH1234567', 'hg')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('HHDPA1234567', 'hg')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('HOUO1234567', 'hg')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('HOUU1234567', 'hg')));
//
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('1X2H2234567', 'pin')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('1X2A2234567', 'pin')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('1X2D2234567', 'pin')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('HDPH2234567', 'pin')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('HDPA2234567', 'pin')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('OUO2234567', 'pin')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('OUUH2234567', 'pin')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('H1X2H2234567', 'pin')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('H1X2A2234567', 'pin')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('H1X2D2234567', 'pin')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('HHDPH2234567', 'pin')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('HHDPA2234567', 'pin')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('HOUO2234567', 'pin')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('HOUU2234567', 'pin')));
  } catch (\Exception $e) {
    echo '!';
  }
 usleep(10000000);
}


function testData($marketId, $provider)
{
  $permittedChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

  return [
      "request_uid" => str_shuffle($permittedChars),
      "request_ts" => milliseconds(),
      "command" => "minmax",
      "sub_command" => "transform",
      "data" => [
          "provider" => $provider,
          "sport" => 1,
          "market_id" => $marketId,
          "odds" => 1.22,//number_format(rand(80, 300) / 100, 2, '.', ','),
          "minimum" => "50.00",
          "maximum" => "100000.00",
	  "timestamp" => milliseconds() - 189,
	  "message" => ""
       ],
       "message" => "",
       "response_message" => ""
  ];
}

function milliseconds()
{
  $mt = explode(' ', microtime());
  return bcadd($mt[1], $mt[0], 8);
}
