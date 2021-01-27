<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\Eloquent\JsonEncodingException;
use JsonException;

class WalletService
{
    public $url;
    public $token;
    public $refreshToken;
    public $expiresIn;
    public $clientId;
    public $clientSecret;

    private $http;

    public function __construct($url, $clientId, $clientSecret)
    {
        $this->url          = $url;
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
        $this->http         = new Client();
    }

    public function getAccessToken(string $scopes)
    {
        $response = $this->http->request('POST', $this->url . '/api/v1/oauth/token', [
            'form_params' => [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type'    => 'client_credentials',
                'scopes'        => $scopes
            ]
        ]);

        $response = json_decode($response->getBody());

        return $response;
    }

    public function refreshToken(string $refreshToken)
    {
        $response = $this->http->request('POST', $this->url . '/api/v1/oauth/token', [
            'form_params' => [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken
            ]
        ]);

        $response = json_decode($response->getBody());

        return $response;
    }

    public function getBalance(string $token, string $uuid, string $currency = null)
    {
        try {
            $params = 'uuid=' . $uuid;

            if (!empty($currency)) {
                $params .= '&currency=' . $currency;
            }

            $response = $this->http->request('GET', $this->url . '/api/v1/wallet/balance?' . $params, [
                'headers' => [
                    'Authorization'    => 'Bearer ' . $token,
                    'X-Requested-With' => 'XMLHttpRequest'
                ]
            ]);

            $response = json_decode($response->getBody());
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());
        }

        return $response;
    }

    public function getBatchBalance (string $token, array $uuids, string $currency = null)
    {
        try {
            $uuids  = http_build_query(['uuids' => $uuids]);
            $params = $uuids;

            if (!empty($currency)) {
                $params .= '&currency=' . $currency;
            }

            $response = $this->http->request('GET', $this->url . '/api/v1/wallet/balance/batch?' . $params, [
                'headers' => [
                    'Authorization'    => 'Bearer ' . $token,
                    'X-Requested-With' => 'XMLHttpRequest'
                ]
            ]);

            $response = json_decode($response->getBody());
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());
        }

        return $response;
    }

    public function addBalance(string $token, string $uuid, string $currency, float $amount, string $reason)
    {
        try {
            $response = $this->http->request('POST', $this->url . '/api/v1/wallet/credit', [
                'form_params' => [
                    'uuid'     => $uuid,
                    'amount'   => $amount,
                    'currency' => $currency,
                    'reason'   => $reason
                ],
                'headers' => [
                    'Authorization'    => 'Bearer ' . $token,
                    'X-Requested-With' => 'XMLHttpRequest'
                ]
            ]);

            $response = json_decode($response->getBody());
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());
        }

        return $response;
    }

    public function subtractBalance(string $token, string $uuid, string $currency, float $amount, string $reason)
    {
        try {
            $response = $this->http->request('POST', $this->url . '/api/v1/wallet/debit', [
                'form_params' => [
                    'uuid'     => $uuid,
                    'amount'   => $amount,
                    'currency' => $currency,
                    'reason'   => $reason
                ],
                'headers' => [
                    'Authorization'    => 'Bearer ' . $token,
                    'X-Requested-With' => 'XMLHttpRequest'
                ]
            ]);

            $response = json_decode($response->getBody());
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());
        }

        return $response;
    }
}
