<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class WalletService
{
  public $url;
  private $clientId;
  private $clientSecret;
  private $http;

  public function __construct($url, $clientId, $clientSecret)
  {
    $this->url          = $url;
    $this->clientId     = $clientId;
    $this->clientSecret = $clientSecret;
    $this->http         = new Client();
  }

  public function getAccessToken()
  {
    $response = $this->http->request('POST', $this->url.'/api/v1/oauth/token', [
      'form_params' => [
        'client_id'     => $this->clientId,
        'client_secret' => $this->clientSecret,
        'grant_type'    => 'client_credentials',
        'scopes'        => 'wallet'
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
      $response = $this->http->request('GET', $this->url.'/api/v1/wallet/balance?' . $params, [
        'headers' => [
          'Authorization'     => 'Bearer ' . $token,
          'Content-Type'      => 'application/json',
          'X-Requested-With'  => 'XMLHttpRequest'
        ]
      ]);
      $response = json_decode($response->getBody());
    } catch(ClientException $e) {
      $response = json_decode($e->getResponse()->getBody()->getContents());
    }
    return $response;
  }
}