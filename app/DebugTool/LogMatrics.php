<?php
namespace App\DebugTool;

class LogMatrics
{
	
	protected $debug=true;
	public function MinMax($key, $payload)
	{
		if ($this->debug)
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
		if ($this->debug)
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