<?php
namespace App\DebugTool;

class LogMatrics
{
	
	
	public function MinMax($key, $payload)
	{
		$debug = env('DEBUG_SEND',false);
		if ($debug)
		{
			$data = ['payload' =>$payload, 'key' => $key];
			$url = env('DEBUGGING_URL', 'http://localhost:8080/api/');
			$urlendpoint = $url . 'minmaxlog';
			$ch = curl_init($urlendpoint);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_exec($ch);

		}
	}

	public function BetPlace($key,$payload)
	{
		$debug = env('DEBUG_SEND',false);
		if ($debug)
		{
			$data = ['payload' =>$payload, 'key' => $key];
			$url = env('DEBUGGING_URL', 'http://localhost:8080/api/');
			$urlendpoint = $url . 'betplacelog';
			$ch = curl_init($urlendpoint);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_exec($ch);
		}
	}

}

?>