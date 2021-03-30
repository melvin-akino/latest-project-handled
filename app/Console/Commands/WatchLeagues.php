<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WatchLeagues extends Command {
	/**
	* The name and signature of the console command.
	*
	* @var string
	*/
	protected $signature='watch:leagues';

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description='Real-time monitor of Leagues system';

	private $UIDMap;
	private $catchAll;
	private $oddsLeagues;
	private $oddsEvents;
	private $UIDIndex;

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
		$this->UIDIndex=[];
	}

	public function handleUIDIndex($payload) {
		$provider=$payload["data"]["provider"];
		$schedule=$payload["data"]["schedule"];

		if(!array_key_exists($provider,$this->UIDIndex)) {
                                $this->UIDIndex[$provider]=[];
		}
		if(!array_key_exists($schedule,$this->UIDIndex[$provider])) {
			$this->UIDIndex[$provider][$schedule]=[];
		}

		if(sizeof($this->UIDIndex[$provider][$schedule])>5) {
			array_shift($this->UIDIndex[$provider][$schedule]);
		}

		if(!in_array($payload["request_uid"],$this->UIDIndex[$provider][$schedule])) {
			array_push($this->UIDIndex[$provider][$schedule],$payload["request_uid"]);
		}

	}

	public function handleUIDMap($payload) {
		$stats=[];
		$now=microtime(true);

		# this is a simple use of the tool.  This will just dump all the messages.  Useful to seeing raw messages,
		# but not useful in case where a lot of people are actively testing, since you will be flooded with msgs
		#var_dump($payload);

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

				$this->handleUIDIndex($payload);

				$provider=$payload["data"]["provider"];
				$schedule=$payload["data"]["schedule"];
				$stats["time"]=$now;
				$stats["provider"]=$provider;
				$stats["schedule"]=$schedule;
				switch($payload["command"]) {
					case "odd":
						$league=$payload["data"]["leagueName"];

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
						if(!array_key_exists("odds",$this->UIDMap[$payload["request_uid"]])) {
							$this->UIDMap[$payload["request_uid"]]["odds"]=[];
						}
						if(!in_array($league,$this->UIDMap[$payload["request_uid"]]["odds"])) {
							$this->UIDMap[$payload["request_uid"]]["odds"][]=$league;
						}

						break;
					case "league":
						$stats["leagues"]=$payload["data"]["leagues"];
						$stats["processTime"]=$processTime;
						$this->UIDMap[$payload["request_uid"]]["leagues"]=$stats;
						break;
					case "event":
						if(!array_key_exists("events",$this->UIDMap[$payload["request_uid"]])) {
							$this->UIDMap[$payload["request_uid"]]["events"]=[
								"notFound"=>0,
							];
						}
						foreach($payload["data"]["event_ids"] as $e) {
							if(array_key_exists($e,$this->oddsEvents[$provider][$schedule])) {
								#echo "search for ..".$this->oddsEvents[$provider][$schedule][$e]."..\n";
								#var_dump($this->UIDMap[$payload["request_uid"]]["events"]);
								#$result=in_array($this->oddsEvents[$provider][$schedule][$e],$this->UIDMap[$payload["request_uid"]]["events"],true);
								#echo "result is:..".$result."..\n";
								if(!in_array($this->oddsEvents[$provider][$schedule][$e],$this->UIDMap[$payload["request_uid"]]["events"],true)) {
									$this->UIDMap[$payload["request_uid"]]["events"][]=$this->oddsEvents[$provider][$schedule][$e];
								}
							}
							else {
								$this->UIDMap[$payload["request_uid"]]["events"]["notFound"]++;
							}
						}
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
		$payload=json_decode($message->payload,true);
		$this->handleUIDMap($payload);
		switch($message->topic_name) {
			case "hg_req":
				#$this->handleHGRequest($payload);
				break;
			case "isn_req":
				#$this->handleISNRequest($payload);
				break;
			case "SCRAPING-ODDS":
				#$this->handleOddsReply($payload);
				break;
		}
		#var_dump($message);
	}


	private function getUIDIndex($provider,$schedule) {
		$return="";

		if(array_key_exists($provider,$this->UIDIndex) && array_key_exists($schedule,$this->UIDIndex[$provider])) {
			$myUIDs=$this->UIDIndex[$provider][$schedule];
		}
		else {
			$myUIDs=[];
		}
		foreach($myUIDs as $m) {
			// count odds json leagues;
			$oddCount=0;
			$oddCount=sizeof($this->UIDMap[$m]["odds"]);

			// count leagues json leagues;
			$leagueCount=0;
			if(array_key_exists("leagues",$this->UIDMap[$m]) && array_key_exists("leagues",$this->UIDMap[$m]["leagues"])) {
				$leagueCount=sizeof($this->UIDMap[$m]["leagues"]["leagues"]);
			}

			// count events json leagues;
			$eventsCount=0;
			if(array_key_exists("events",$this->UIDMap[$m])) {
				$eventsCount=sizeof($this->UIDMap[$m]["events"])-1;
			}

			$return=$return."".$m." -- Odds: ".$oddCount."..Leagues: ".$leagueCount."..Events: ".$eventsCount."\n";
		}
		return $return;
	}


	public function printStats() {
		//Clear screen
		echo "\033[2J";
		echo "\033[H";

		//Print UIDMap Info
		#var_dump($this->UIDIndex);
		echo "HG:\n";
		echo "+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.\n";
		echo "INPLAY:\n";
		echo "-------\n";
		echo $this->getUIDIndex("hg","inplay")."\n\n";
		echo "TODAY:\n";
		echo "------\n";
		echo $this->getUIDIndex("hg","today")."\n\n";
		echo "EARLY:\n";
		echo "------\n";
		echo $this->getUIDIndex("hg","early")."\n\n";
		echo "\n\n";
		echo "ISN:\n";
		echo "+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.+.\n";
		echo "INPLAY:\n";
		echo "-------\n";
		echo $this->getUIDIndex("isn","inplay")."\n\n";
		echo "TODAY:\n";
		echo "------\n";
		echo $this->getUIDIndex("isn","today")."\n\n";
		echo "EARLY:\n";
		echo "------\n";
		echo $this->getUIDIndex("isn","early")."\n\n";

	}

	/**
	* Execute the console command.
	*
	* @return mixed
	*/
	public function handle() {
		$groupname="ML-C-ODDS";
		$topicList=[
			"hg_req",
			"isn_req",
			"SCRAPING-ODDS",
			"SCRAPING-PROVIDER-LEAGUES",
			"SCRAPING-PROVIDER-EVENTS",
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