<?php

namespace App\Helpers;


class TransformRequestOdds
{
 
    /**
     * Validate
     *
     * @param array $message
     * @param mixed $key
     * @param Swoole\Table $table
     * @return boolean
     */
    public function validate(array $message = [], Swoole\Table $table)
    {
        $requestId = $message->request_uid;
        $requestTs = $message->request_ts;
        $provider = $message->data->provider;
        $sportId = $message->data->sportId;
        $scheduleType = $message->data->type;

        $requestKey = "provider:".strtolower($provider)."|reqId:".$requestId."|sId:".$requestTs."|type:".$scheduleType;

    }

    /**
     * Retrieve
     *
     * @param string $key
     * @param Swoole\Table $table
     * @return void
     */
    public function get(string $key, Swoole\Table $table)
    {
        $table->get($key);
    }

    /**
     * Store
     *
     * @param string $key
     * @param array $data
     * @param Swoole\Table $table
     * @return void
     */
    public function store(string $key, array $data, Swoole\Table $table)
    {
        $table->set($requestKey, $data);
    }

    /**
     * Delete
     *
     * @param string $key
     * @param Swoole\Table $table
     * @return void
     */
    public function delete(string $key, Swoole\Table $table)
    {
        $table->del($key);
    }

    /**
     * Find
     *
     * @param string $key
     * @param Swoole\Table $table
     * @return void
     */
    public function find(string $key, Swoole\Table $table)
    {
        return $table->exist($key);
    }

}