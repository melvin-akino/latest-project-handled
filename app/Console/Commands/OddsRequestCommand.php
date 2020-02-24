<?php

namespace App\Console\Commands;

use App\Models\SystemConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Handlers\ProducerHandler;
use Exception;
use Illuminate\Support\Facades\Log;

class OddsRequestCommand extends Command
{
    protected $signature = 'odds:request';

    protected $description = 'Odds Scraping Request';

    protected $config;

    protected $systemConfiguration;

    /**
     * Topic name
     */
    const KAFKA_TOPIC = 'odds_scrape_request';

    /**
     * Kafka producer
     *
     * @var \App\Handlers\Kafka\ProducerHandler
     */
    protected $producerHandler;

    private $select;

    private $variable;

    public function __construct(ProducerHandler $producerHandler)
    {
        parent::__construct();

        $this->producerHandler = $producerHandler;
    }

    public function handle()
    {
        $this->provid = DB::table('providers')->where('is_enabled', true)->get()->toArray();
        $this->sports = DB::table('sports')->where('is_enabled', true)->get()->toArray();

        $this->systemConfiguration = new SystemConfiguration();

        $this->schedule_mapping = config('scraping.schedule_mapping');

        $this->schedule_mapping_field = config('scraping.schedule_mapping_field');

        $refresh_db_interval = config('scraping.refresh_db_interval');

        $i = 0;
        while (true) {
            if ($i % $refresh_db_interval == 0) {
                $this->refresh_db_config();
            }

            $request = [];
            foreach ($this->schedule_mapping as $key => $scheduleType) {
                foreach ($this->config as $conf) {
                    if (in_array($conf['type'], $scheduleType)) {
                        $request[$key][$this->schedule_mapping_field[$conf['type']]] = $conf['value'];
                    }
                }
            }

            foreach ($request as $key => $req) {
                $this->scheduleType = $key;
                if ($i % $req['timer'] == 0) {
                    for ($interval = 0; $interval < $req['requestNumber']; $interval++) {
                        $this->sendPayload();
                        usleep($req['requestInterval'] * 1000);
                    }
                }
            }

            $i++;
            sleep(1);
        }
    }

    private function sendPayload()
    {
        foreach ($this->providers as $provider) {
            foreach ($this->sports as $sport) {
                $uid = uniqid()

                $payload = [
                    'request_uid' => $uid,
                    'request_ts'  => $this->milliseconds(),
                    'command'     => 'odd',
                    'sub_command' => 'scrape',
                ];
                $payload['data'] = [
                    'provider' => strtolower($provider->alias),
                    'schedule' => $this->scheduleType,
                    'sport'    => $sport->id
                ];

                $this->pushToKafka($payload, $uid);
            }
        }
    }

    /**
     * Return microtime in milliseconds
     *
     * @return string
     */
    private function milliseconds()
    {
        $mt = explode(' ', microtime());
        return bcadd($mt[1], $mt[0], 8);
    }

    /**
     * Retrieve update scraping config parameters
     *
     * @return void
     */
    private function refresh_db_config()
    {
        $this->systemConfiguration->where(true, true);
        foreach ($this->schedule_mapping as $scheduleType) {
            foreach ($scheduleType as $where) {
                $this->systemConfiguration->orWhere('type', $where);
            }
        }
        $this->config = $this->systemConfiguration->select('type', 'value')->get()->toArray();
    }

    /**
     * Push command message to kafka
     *
     * @param  array $message
     * @param  string $key
     * @return void
     */
    private function pushToKafka(array $message = [], string $key)
    {
        try {
            $this->producerHandler->setTopic(self::KAFKA_TOPIC)
                ->send(json_encode($message), $key);
        } catch (Exception $e) {
            Log::critical(self::PUBLISH_ERROR_MESSAGE, [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }
}
