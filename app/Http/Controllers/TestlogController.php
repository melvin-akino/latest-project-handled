<?php

namespace App\Http\Controllers;
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Maxbanton\Cwh\Handler\CloudWatch;
use Monolog\Logger;
use Monolog\Formatter\JsonFormatter;

use Illuminate\Http\Request;

class TestlogController extends Controller
{
    //

    public function testlog()
    {
    	echo "hello";
    	try {
		    $sdkParams = [
		    'region' => 'us-east-2',
		    'version' => 'latest',
		    'credentials' => [
		        'key' => 'AKIA3D5LECPYHURYLNVJ',
		        'secret' => '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',
		        
		    	]
			];
			$client = new CloudWatchLogsClient($sdkParams);
			
			$groupName = 'php-logtest';

		// Log stream name, will be created if none
			$streamName = 'ec2-instance-1';

			// Days to keep logs, 14 by default. Set to `null` to allow indefinite retention.
			$retentionDays = 30;

			// Instantiate handler (tags are optional)
			$handler = new CloudWatch($client, $groupName, $streamName, $retentionDays, 10000, ['my-awesome-tag' => 'tag-value']);

			// Optionally set the JsonFormatter to be able to access your log messages in a structured way
			$handler->setFormatter(new JsonFormatter());

			// Create a log channel
			$log = new Logger('name');

			// Set handler
			$log->pushHandler($handler);

			// Add records to the log
			$log->debug('Foo');
			$log->warning('Bar');
			$log->error('Baz');
		}catch (\Exception $e) {
			echo $e->getMessage();
		}
	}
}
