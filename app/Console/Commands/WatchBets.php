<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WatchBets extends Command {
	/**
	* The name and signature of the console command.
	*
	* @var string
	*/
	protected $signature='watch:bets';

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description='Real-time monitor of Bets system';

	private $UIDMap;
	private $catchAll;
	private $oddsLeagues;
	private $oddsEvents;

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
		$this->oddsLeagues=[];
		$this->oddsEvents=[];
		$this->catchAll=[];
	}


	public function handleUIDMap($payload) {
		$stats=[];
		$now=microtime(true);

		# this is a simple use of the tool.  This will just dump all the messages.  Useful to seeing raw messages,
		# but not useful in case where a lot of people are actively testing, since you will be flooded with msgs
		var_dump($payload);
		return;

		# this is a matching test case.  This requires you to know specifically which messages you want to filter for
		# by some unique identifier.  In this case I am filtering for market_ids...
		# This is useful when a ton of people are using the system.
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

		#this is the general case where we count messages, and keep stats for printing.
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

				$provider=$payload["data"]["provider"];
				$schedule=$payload["data"]["schedule"];
				$league=$payload["data"]["leagueName"];
				$stats["time"]=$now;
				$stats["provider"]=$provider;
				$stats["schedule"]=$schedule;
				switch($payload["command"]) {
					case "odd":
						// add to oddsLeagues
						if(!array_key_exists($provider,$this->oddsLeagues)) {
							$this->oddsLeagues[$provider]=[];
						}
						if(!array_key_exists($schedule,$this->oddsLeagues[$provider])) {
							$this->oddsLeagues[$provider][$schedule]=[];
						}
						if(!array_key_exists($league,$this->oddsLeagues[$provider][$schedule])) {
							$this->oddsLeagues[$provider][$schedule][]=$league;
						}

						// add to oddsEvents
						if(!array_key_exists($provider,$this->oddsEvents)) {
							$this->oddsEvents[$provider]=[];
						}
						if(!array_key_exists($schedule,$this->oddsEvents[$provider])) {
							$this->oddsEvents[$provider][$schedule]=[];
						}
						foreach($payload["data"]["events"] as $e) {
							$this->oddsEvents[$provider][$schedule][$e["eventId"]]=$league;
						}

						// add to leagueName request_uid aggregate for odds
						#if(!array_key_exists("odds",$this->UIDMap[$payload["request_uid"])) {
							#$this->UIDMap["odds"]=[];
						#}

						break;
					case "league":
						$stat["leagues"]=$payload["data"]["leagues"];
						$stat["processTime"]=$processTime;
						$this->UIDMap[$payload["request_uid"]]["leagues"]=$stats;
						break;
					case "event":
						break;
				}


			}

		}
		else {
			//Request
			if(array_key_exists($payload["request_uid"],$this->UIDMap)) {
				$this->UIDMap["duplicates"]++;
			}

			$stats["time"]=$now;
			$stats["provider"]=$payload["data"]["provider"];
			$stats["schedule"]=$payload["data"]["schedule"];

			$this->UIDMap[$payload["request_uid"]]["request"]=$stats;
		}
	}


	public function message($message) {
		echo "**************** [ NEW BET ] **********************\n";
		$payload=json_decode($message->payload,true);
		$this->handleUIDMap($payload);
		switch($message->topic_name) {
			case "hg_req":
				$this->handleHGRequest($payload);
				break;
			case "isn_req":
				$this->handleISNRequest($payload);
				break;
			case "SCRAPING-ODDS":
				$this->handleOddsReply($payload);
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
		$groupname="ML-C-ODDS";
		$topicList=[
			"hg_bet_req",
			"isn_bet_req",
			"PLACED-BET",
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
			#\Swoole\Timer::tick(5000,[$this,"printStats"]);
			echo "Watching Bets...\n";
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