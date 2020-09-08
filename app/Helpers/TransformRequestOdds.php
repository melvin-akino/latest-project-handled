<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Swoole\Table;

class TransformRequestOdds
{
 
    /**
     * Validate the current request
     *
     * @param array $message
     * @param Swoole\Table $table
     * @return boolean
     */
    public function isValidRequest(array $message = [], Table $table)
    {
        $requestId = $message->request_uid;
        $requestTs = $message->request_ts;
        $requestCommand = $message->command;
        $requestSubCommand = $message->sub_command;
        $provider = $message->data->provider;
        $sport = $message->data->sport;
        $schedule = $message->data->schedule;

        if ($requestCommand != 'odd')
        {
            throw new Exception("Invalid command");
        }

        if ($requestSubCommand != 'transform')
        {
            throw new Exception("Invalid sub command");
        }

        $requestKey = "sId:".$sport.":provider:".strtolower($provider).":rId:".$requestId.":schedule:".$schedule;

        $currentTs = $table->get($requestKey);

        if ($currentTs === false)
        {
            // new request
            return true;  
        }
        
        if (bccomp($requestTs, $currentTs, 8) < 0)
        {
            throw new Exception("Request timestamp is old");
        }

        // update currentTs
        // $table->set($requestKey, $requestTs);

        return true;
    }

    /**
     * Get the current provider
     *
     * @param string $provider
     * @param Swoole\Table $table
     * @return integer $providerId
     */
    public function getProvider(string $provider, Table $table)
    {        
        $data = $table->get(strtolower($provider));

        if (($data === false) || (!isset($data['id'])))
        {
            throw new Exception("Provider doesn't exist");
        }

        return $data['id'];
    }

    /**
     * Get the current sport
     *
     * @param string $sport
     * @param Swoole\Table $table
     * @return void
     */
    public function getSport(string $sport, Table $table)
    {        
        if (!$table->exist($sport))
        {
            throw new Exception("Sport doesn't exist");
        }
    }

    /**
     * Get the current league
     *
     * @param array $message
     * @param integer $providerId
     * @param Swoole\Table $table
     * @return array
     */
    public function getLeague(array $message = [], integer $providerId, Table $table)
    {
        $sport = $message->data->sport;
        $league = $message->data->leagueName;

        $requestKey = "sId:".$sport.":pId:".$providerId.":league:".Str::slug($league);

        $data = $table->get($requestKey);

        if ($data === false)
        {
            return false;  
        }

        return $data;
    }

    /**
     * Get the current team (home or away)
     *
     * @param array $message
     * @param integer $providerId
     * @param integer $type (1=Home; 2=Away)
     * @param Swoole\Table $table
     * @return array
     */
    public function getTeam(array $message = [], integer $providerId, string $type, Table $table)
    {
        $sport = $message->data->sport;
        $team = $message->data->homeTeam;
        if ($type == 2)
        {
            $team = $message->data->awayTeam;
        }

        $requestKey = "sId:".$sport.":pId:".$providerId.":team:".Str::slug($team);

        $data = $table->get($requestKey);

        if ($data === false)
        {
            return false;  
        }

        return data;
    }

    /**
     * Retrieve
     *
     * @param string $key
     * @param Swoole\Table $table
     * @return void
     */
    public function get(string $key, Table $table)
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
    public function store(string $key, array $data, Table $table)
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
    public function delete(string $key, Table $table)
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
    public function find(string $key, Table $table)
    {
        return $table->exist($key);
    }

}