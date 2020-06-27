<?php

$conf = new RdKafka\Conf();
$conf->set('group.id', 'multiline');
//$conf->set('metadata.broker.list', 'ec2-3-133-143-124.us-east-2.compute.amazonaws.com:9092');
$conf->set('metadata.broker.list', 'kafka:9092');
$conf->set('auto.offset.reset', 'latest');
$conf->set('enable.auto.commit', 'false');

$rk = new RdKafka\Producer($conf);
$topic = $rk->newTopic("OPEN-ORDERS");

//testData("", "Union La Calera", "Coquimbo", "hg", 1, 'early', 1234567, 2345678, "2020-03-18T01:00:00.000+04:00")

// while(true) {
  try {
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData()));  
	echo '.';
  } catch (\Exception $e) {
    echo '!';
  }
//  usleep(5000000);
// }


function testData()
{
  $permittedChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

  return array (
    'request_uid' => '77f61545-6756-4463-97a1-14',
    'request_ts' => '123456789',
    'command' => 'open orders',
    'sub_command' => 'transform',
    'data' => 
    array (
      0 => 
      array (
        'provider' => 'hg',
        'sport' => 1,
        'id' => 1,
        'user_name' => 'chca016NP',
        'status' => 'success',
        'odds' => '1.06',
        'actual_stake' => '50.0',
        'actual_to_win' => '53.0',
        'bet_id' => 'OU12544568785',
        'reason' => '',
      ),
      1 => 
      array (
        'provider' => 'hg',
        'sport' => 1,
        'id' => 2,
        'user_name' => 'chca016NP',
        'status' => 'success',
        'odds' => '1.60',
        'actual_stake' => '50.0',
        'actual_to_win' => '30.0',
        'bet_id' => 'OU12544493270',
        'reason' => '',
      ),
      2 => 
      array (
        'provider' => 'hg',
        'sport' => 1,
        'id' => 3,
        'user_name' => 'chca016NP',
        'status' => 'success',
        'odds' => '1.75',
        'actual_stake' => '50.0',
        'actual_to_win' => '37.5',
        'bet_id' => 'OU12544489265',
        'reason' => '',
      ),
      3 => 
      array (
        'provider' => 'hg',
        'sport' => 1,
        'id' => 4,
        'user_name' => 'chca016NP',
        'status' => 'success',
        'odds' => '1.76',
        'actual_stake' => '50.0',
        'actual_to_win' => '38.0',
        'bet_id' => 'OU12544458839',
        'reason' => '',
      ),
      4 => 
      array (
        'provider' => 'hg',
        'sport' => 1,
        'id' => 5,
        'user_name' => 'chca016NP',
        'status' => 'success',
        'odds' => '1.77',
        'actual_stake' => '50.0',
        'actual_to_win' => '38.5',
        'bet_id' => 'OU12544436968',
        'reason' => '',
      ),
      5 => 
      array (
        'provider' => 'hg',
        'sport' => 1,
        'id' => 6,
        'user_name' => 'chca016NP',
        'status' => 'success',
        'odds' => '1.77',
        'actual_stake' => '50.0',
        'actual_to_win' => '38.5',
        'bet_id' => 'OU12544420354',
        'reason' => '',
      ),
      6 => 
      array (
        'provider' => 'hg',
        'sport' => 1,
        'id' => 7,
        'user_name' => 'chca016NP',
        'status' => 'success',
        'odds' => '1.78',
        'actual_stake' => '50.0',
        'actual_to_win' => '39.0',
        'bet_id' => 'OU12544413928',
        'reason' => '',
      ),
      7 => 
      array (
        'provider' => 'hg',
        'sport' => 1,
        'id' => 8,
        'user_name' => 'chca016NP',
        'status' => 'success',
        'odds' => '1.78',
        'actual_stake' => '50.0',
        'actual_to_win' => '39.0',
        'bet_id' => 'OU12544412915',
        'reason' => '',
      ),
      8 => 
      array (
        'provider' => 'hg',
        'sport' => 1,
        'id' => 9,
        'user_name' => 'chca016NP',
        'status' => 'success',
        'odds' => '1.79',
        'actual_stake' => '50.0',
        'actual_to_win' => '39.5',
        'bet_id' => 'OU12544402101',
        'reason' => '',
      ),
    ),
  );
}

