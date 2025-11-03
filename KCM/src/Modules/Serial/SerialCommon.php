<?php

namespace Kova\Kcm\Modules\Serial;

use Curl\Curl;
use Kova\Kcm\Modules\Common\Common as Common;


class SerialCommon
{

  public $config;
  public $common;

  public function __construct($config)
  {

    $this->config = $config;
    $this->common = new Common($this->config);
  }

  private function getAuthToken()
  {


    $curlIP = 'http://' . $this->config['VerintIPport'] . '/wfo/rest/core-api/auth/token';
    $curl = new Curl();

    $curl->get($curlIP, [
      'user' => 'wsuperuser',
      'password' => 'pumpkin1'
    ]);

    $token = $this->common->get_string_between($curl->response, 'token":"', '"');

    return $token;
  }

  public function getEmpList()
  {
    $authToken = $this->getAuthToken();

    $curlIP = 'http://' . $this->config['VerintIPport'] . '/wfo/rest/core-api/v2/emp/get';

    $curl = new Curl();

    $curl->setHeader('Impact360AuthToken', $authToken);
    $curl->setHeader('Content-Type', 'application/json');

    $curl->get($curlIP, );
  }
}
