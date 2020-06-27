<?php

$conf = new RdKafka\Conf();
$conf->set('group.id', 'multiline');
$conf->set('metadata.broker.list', 'ec2-3-133-143-124.us-east-2.compute.amazonaws.com:9092');
$conf->set('auto.offset.reset', 'latest');
$conf->set('enable.auto.commit', 'false');

$rk = new RdKafka\Producer($conf);
$topic = $rk->newTopic("ALEXANDER-SCRAPING-ODDS");

//testData("", "Union La Calera", "Coquimbo", "hg", 1, 'early', 1234567, 2345678, "2020-03-18T01:00:00.000+04:00")

// while(true) {
	try {
		$testData = [
			testData("Chile - Primera Division", "Union La Calera", "Coquimbo", "hg", 1, 'early', 1234567, 2345678, "2020-03-18T01:00:00.000+04:00"),
			testData("Spain Segunda Division", "Racing Santander", "CD Lugo", "hg", 1, 'early', 1234568, 2345679, "2020-03-18T02:00:00.000+04:00"),
			testData("League 1", "Home Team 1", "Away Team 1", "hg", 1, 'early', 1234569, 2345670, "2020-03-18T02:00:00.000+04:00"),
			testData("Chile - Primera Division", "Racing Santander", "Coquimbo", "hg", 1, 'today', 1234560, 2345671, "2020-03-17T01:00:00.000+04:00"),
        	      	testData("Spain Segunda Division", "Union La Calera", "CD Lugo", "hg", 1, 'today', 1234561, 2345672, "2020-03-17T02:00:00.000+04:00"),
			testData("League 1", "Home Team 2", "Away Team 2", "hg", 1, 'today', 1234562, 2345673, "2020-03-17T03:00:00.000+04:00"),
			testData("League 1", "Home Team 3", "Away Team 3", "hg", 1, 'inplay', 1234563, 2345674, "2020-03-17T16:00:00.000+04:00"),
			// testData("League 1", "Home Team 4", "Away Team 4", "hg", 1, 'inplay', 1234564, 2345675, "2020-03-17T16:30:00.000+04:00"),
			testData("Chile - Primera Division", "Home Team 1", "Away Team 1", "hg", 1, 'inplay', 1234565, 235676, "2020-03-17T15:30:00.000+04:00"),
			testData("Chile - Primera Division", "Home Team 2", "Away Team 2", "hg", 1, 'inplay', 1234566, 2345677, "2020-03-17T15:45:00.000+04:00"),
		];
		echo '.';
		foreach ($testData as $data) {
			echo '*';
			$topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($data));
		}
	} catch (\Exception $e) {
		echo '!';
	}
	// usleep(5000000);
// }


function testData($leagueName, $homeTeam, $awayTeam, $providerAlias, $sport, $schedule, $eventId, $otherEventId, $refSchedule)
{
	$permittedChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

  return array (
    'request_uid' => str_shuffle($permittedChars),
    'request_ts' => milliseconds(),
    'command' => 'odd',
    'sub_command' => 'transform',
    'data' =>
    array (
      'provider' => $providerAlias,
      'schedule' => $schedule,
      'sport' => $sport,
      'leagueName' => $leagueName,
      'homeTeam' => $homeTeam,
      'awayTeam' => $awayTeam,
      'referenceSchedule' => $refSchedule,
      'running_time' => '',
      'home_score' => 0,
      'away_score' => 0,
      'home_redcard' => 0,
      'away_redcard' => 0,
      'id' => 12,
      'events' =>
      array (
        0 =>
        array (
          'eventId' => $eventId,
          'market_type' => 1,
          'market_odds' =>
          array (
            0 =>
            array (
              'oddsType' => '1X2',
              'marketSelection' =>
              array (
                0 =>
                array (
                  'market_id' => 'MH3479695',
                  'indicator' => 'Home',
                  'odds' => number_format(rand(80, 300) / 100, 2, '.', ','),
                ),
                1 =>
                array (
                  'market_id' => 'MC3479695',
                  'indicator' => 'Away',
                  'odds'      => number_format(rand(80, 300) / 100, 2, '.', ','),
	  	),
		2 =>
		array (
		  'market_id' => 'MX3479695',
		  'indicator' => 'Draw',
		  'odds'      => number_format(rand(80, 300) / 100, 2, '.', ',')
		)
              ),
            ),
            1 =>
            array (
              'oddsType' => 'HDP',
              'marketSelection' =>
              array (
                0 =>
                array (
                  'market_id' => 'RH3479695',
                  'indicator' => 'Home',
                  'odds' => '0.909',
                  'points' => '-1.5',
                ),
                1 =>
                array (
                  'market_id' => 'RC3479695',
                  'indicator' => 'Away',
                  'odds' => '0.891',
                  'points' => '+1.5',
                ),
              ),
            ),
            2 =>
            array (
              'oddsType' => 'OU',
              'marketSelection' =>
              array (
                0 =>
                array (
                  'market_id' => 'OUC3479695',
                  'indicator' => 'Home',
                  'odds' => '0.843',
                  'points' => 'O 140',
                ),
                1 =>
                array (
                  'market_id' => 'OUH3479695',
                  'indicator' => 'Away',
                  'odds' => '0.917',
                  'points' => 'U 140',
                ),
              ),
            ),
            3 =>
            array (
              'oddsType' => 'HT HOME GOALS',
              'marketSelection' =>
              array (
                0 =>
                array (
                  'market_id' => 'OUHO3479695',
                  'indicator' => 'Home',
                  'odds' => '0.930',
                  'points' => 'O 71',
                ),
                1 =>
                array (
                  'market_id' => 'OUHU3479695',
                  'indicator' => 'Away',
                  'odds' => '0.830',
                  'points' => 'U 71',
                ),
              ),
            ),
            4 =>
            array (
              'oddsType' => 'HT AWAY GOALS',
              'marketSelection' =>
              array (
                0 =>
                array (
                  'market_id' => 'OUCO3479695',
                  'indicator' => 'Home',
                  'odds' => '0.920',
                  'points' => 'O 69.5',
                ),
                1 =>
                array (
                  'market_id' => 'OUCU3479695',
                  'indicator' => 'Away',
                  'odds' => '0.840',
                  'points' => 'U 69.5',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
  );
}

function milliseconds()
{
	$mt = explode(' ', microtime());
	return bcadd($mt[1], $mt[0], 8);
}

