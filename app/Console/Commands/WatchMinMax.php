<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WatchMinMax extends Command {
	/**
	* The name and signature of the console command.
	*
	* @var string
	*/
	protected $signature='watch:minmax';

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description='Real-time monitor of MinMax system';

	private $isnRequests;
	private $hgRequests;
	private $UIDMap;
	private $catchAll;

	private $rk;
	private $queue;

	/**
	* Create a new command instance.
	*
	* @return void
	*/
	public function __construct() {
		parent::__construct();

		$this->UIDMap=[
			"duplicates"=>0,
			"invalidReply"=>0,
		];
		$this->isnRequests=[];
		$this->hgRequests=[];
		$this->catchAll=[];
	}


	public function handleUIDMap($payload) {
		$stats=[];
		$now=microtime(true);

		var_dump($payload);

		#$match=[
			#"MH4757921",
			#"MC4757921",
			#"MH4757925",
			#"MC4757925",
			#"MH4757933",
			#"MC4757933",
		#];

		#$found=false;
		#foreach($match as $m) {
			#if($payload["data"]["market_id"]!=$m) {
			#}
			#else {
				#$found=true;
			#}
		#}

		#if(!$found) {
			##echo "\nEND...".$payload["data"]["market_id"]."..\n";
			#return;
		#}
		#else {
			##echo "\nCONTINUE...".$payload["data"]["market_id"]."..\n";
		#}

		if($payload["sub_command"]=="transform") {
			//Reply
			$valid=true;
			if(array_key_exists($payload["request_uid"],$this->UIDMap)) {
				if(!array_key_exists("request",$this->UIDMap[$payload["request_uid"]])) {
					$valid=false;
					$this->UIDMap["invalidReply"]++;
				}
			}
			else {
				$valid=false;
				$this->UIDMap["invalidReply"];
			}

			if($valid) {
				$reqTime=$this->UIDMap[$payload["request_uid"]]["request"]["time"];
				$processTime=$now-$reqTime;

				$stats["time"]=$now;
				$stats["provider"]=$payload["data"]["provider"];
				$stats["market"]=$payload["data"]["market_id"];

				$this->UIDMap[$payload["request_uid"]]["reply"]=$stats;
				$this->UIDMap[$payload["request_uid"]]["processed"]=true;
				$this->UIDMap[$payload["request_uid"]]["processTime"]=$processTime;
			}

		}
		else {
			//Request
			if(array_key_exists($payload["request_uid"],$this->UIDMap)) {
				$this->UIDMap["duplicates"]++;
			}

			$stats["time"]=$now;
			$stats["provider"]=$payload["data"]["provider"];
			$stats["market"]=$payload["data"]["market_id"];

			$this->UIDMap[$payload["request_uid"]]["request"]=$stats;
		}
	}

	public function handleHGRequest($payload) {
		#echo "***** [ HG REQ ] ************\n";
	}
	public function handleISNRequest($payload) {
		#echo "***** [ ISN REQ ] ************\n";
	}
	public function handleMinmaxResponse($payload) {
		#echo "***** [ MINMAX RESP ] ************\n";
	}


	public function message($message) {
		$payload=json_decode($message->payload,true);
		$this->handleUIDMap($payload);
		switch($message->topic_name) {
			case "hg_minmax_req":
				$this->handleHGRequest($payload);
				break;
			case "isn_minmax_req":
				$this->handleISNRequest($payload);
				break;
			case "MINMAX-ODDS":
				$this->handleMinmaxResponse($payload);
				break;
		}
		#var_dump($message);
	}

	private function UIDCountProvider($which) {
		$count=0;
		foreach($this->UIDMap as $map) {
			if(is_array($map)) {
				if($map["request"]["provider"]==$which) {
					$count++;
				}
			}
		}
		return $count;
	}

	private function UIDAvgReply($which) {
		$time=0;
		$temp=0;
		$count=0;
		foreach($this->UIDMap as $map) {
			if(is_array($map)) {
				if($map["request"]["provider"]==$which && array_key_exists("processed",$map) && $map["processed"]==true) {
					$count++;
					$temp=$temp+$map["processTime"];
				}
			}
		}
		if($count) {
			$time=$temp/$count;
		}

		return $time;
	}

	private function UIDCountNoAnswer($which) {
		$count=0;
		foreach($this->UIDMap as $map) {
			if(is_array($map)) {
				if($map["request"]["provider"]==$which) {
					if(!array_key_exists("processed",$map)) {
						$count++;
					}
				}
			}
		}
		return $count;
	}


	public function printStats() {
		//Clear screen
		echo "\033[2J";
		echo "\033[H";

		//Print UIDMap Info
		echo "Num REQ : ".(count($this->UIDMap)-2)."\n";
		echo "Num DUPE: ".$this->UIDMap["duplicates"]."\n";
		echo "Num BAD : ".$this->UIDMap["invalidReply"]."\n";
		echo "\n";
		echo "ISN Requests:\n";
		echo "=============\n";
		echo "Count    : ".$this->UIDCountProvider("isn")."\n";
		echo "No Answer: ".$this->UIDCountNoAnswer("isn")."\n";
		echo "Avg Time : ".$this->UIDAvgReply("isn")."\n";
		echo "\n";
		echo "HG Requests:\n";
		echo "=============\n";
		echo "Count    : ".$this->UIDCountProvider("hg")."\n";
		echo "No Answer: ".$this->UIDCountNoAnswer("hg")."\n";
		echo "Avg Timei: ".$this->UIDAvgReply("hg")."\n";
	}

	/**
	* Execute the console command.
	*
	* @return mixed
	*/
	public function handle() {
		$groupname="ML-C-MINMAX";
		$topicList=[
			"hg_minmax_req",
			"isn_minmax_req",
			"MINMAX-ODDS",
		];

		$conf=new \RdKafka\Conf();
		$conf->set('group.id',$groupname);

		$this->rk=new \RdKafka\Consumer($conf);
		$this->rk->addBrokers(env('KAFKA_BROKERS'));

		$topicConf=new \RdKafka\TopicConf();
		$topicConf->set('auto.commit.interval.ms',1000);
		$topicConf->set('enable.auto.commit','false');
		$topicConf->set('offset.store.method','broker');
		$topicConf->set('auto.offset.reset','latest');

		$this->queue=$this->rk->newQueue();
		foreach($topicList as $t) {
			$topic=$this->rk->newTopic($t,$topicConf);
			$topic->consumeQueueStart(0,RD_KAFKA_OFFSET_END,$this->queue);
			$topic=null;
		}


		\Co\run(function () {
			\Swoole\Timer::tick(5000,[$this,"printStats"]);
			while(true) {
				$message=$this->queue->consume(0);
				if($message) {
					switch($message->err) {
						case RD_KAFKA_RESP_ERR_NO_ERROR:
							$this->message($message);
							break;
						case RD_KAFKA_RESP_ERR__PARTITION_EOF:
							echo "No more messages; will wait for more\n";
							break;
						case RD_KAFKA_RESP_ERR__TIMED_OUT:
							echo "Timed out\n";
							break;
						default:
							throw new Exception($message->errstr(),$message->err);
							break;
					}
				}
				\Co\System::sleep(0.001);
			}
		});
	}
}