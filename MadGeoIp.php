<?php
namespace mad\tools;

class MadGeoIp {
	private $data = null;

	private function __construct() {
		$ip = $_SERVER["REMOTE_ADDR"];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,'http://www.geoplugin.net/php.gp?ip='.$ip);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$curl = curl_exec($ch);
		curl_close($ch);

		$this->data = @ unserialize($curl);
	}

	public static function getInstance() {
		static $i;
		$i || $i = new self();
		return $i;
	}

	function getCode($ipaddr) {
		return file_get_contents("http://geoip.wtanaka.com/cc/$ipaddr");
	}

	function getLocale( $ip ) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,'http://www.geoplugin.net/php.gp?ip='.$ip);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$rv = curl_exec($ch);
		curl_close($ch);
		return @unserialize($rv);
	}
	function getData() {
		return $this->data;
	}
	function __get( $key ) {
		if ( isset( $this->data['geoplugin_' . $key] ) ) {
			return $this->data['geoplugin_' . $key];
		}
		return false;
	}
}
