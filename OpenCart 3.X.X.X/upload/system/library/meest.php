<?php
class Meest {

	const API_URL = 'https://publicapi.meest.com/';

	private $register;

	private $config;
	private $loger;

	public function __construct($register) {
		$this->config = $register->get('config');
		$this->loger = NEW Log('meest.log');
		return $this;
	}

	protected function makeCall($uri, $data,$parameters) {
		$param = http_build_query($parameters);
		if ($data) {
			$url = self::API_URL . $uri. urlencode($data) . ($param ? '?' . $param : '');
		} else {
			$url = self::API_URL . $uri . ($param ? '?' . $param : '');
		}
		$curlOptions = array(
			CURLOPT_URL				=> $url,
			CURLOPT_RETURNTRANSFER  => true,
			CURLOPT_CONNECTTIMEOUT  => 20,
			CURLOPT_TIMEOUT			=> 60,
			CURLOPT_SSL_VERIFYPEER  => false,
		);

		$ch = curl_init();
		curl_setopt_array($ch, $curlOptions);
		$callResult = curl_exec($ch);
		if (!$callResult) {
		}
		curl_close($ch);

		$callResult = json_decode($callResult, true);
		if (isset($callResult['status'])) {
			return $callResult;
		}
		$this->loger->write('Meest EXPRESS error: ' . $url . "\n" . print_r($callResult, true));
	}

	public function locator($data) {
		$callResult = $this->makeCall('locator/',$data);
		return $callResult;
	}

	public function branches($data ='', $parameters = []) {
        return $this->makeCall('branches/',$data, $parameters);
	}

	public function geo_regions($data) {
        return $this->makeCall('geo_regions/',$data);
	}

	public function geo_districts($data) {
        return $this->makeCall('geo_districts/',$data);
	}

	public function geo_localities($parameters = []) {
        return $this->makeCall('geo_localities/',null, $parameters);
	}

    public function geo_streets($parameters = []) {
        return $this->makeCall('geo_streets/',null, $parameters);
    }
}
