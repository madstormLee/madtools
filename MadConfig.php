<?php
namespace mad\tools; 

class MadConfig extends MadJson {
	private function __construct() {
		$this->load($_SERVER['DOCUMENT_ROOT'] . '/config.json');
		if(! $this->data ) {
			$this->data = (object)[];

			$info = (object) [
				'driver' => 'sqlite',
				'dbname' =>  $_SERVER['DOCUMENT_ROOT'] . '/sqlite.db'
			];
			$this->db = (object)[ "default" => $info ];
		}
		if(! $this->whiteIp) {
			$this->whiteIp = ['127.0.0.1', '::1'];
		}
	}

	public static function getInstance() {
		static $i;
		$i || $i = new self;
		return $i;
	}

	function appendConfig($file) {
		if(! is_file( $file ) ) {
			return $this;
		}
		$json = new MadJson($file);
		foreach( $json as $key => $row ) {
			$this->$key = $row;
		}
		return $this;
	}

	function errorDir() {
		if( isset($this->errorDir)) {
			return $_SERVER['DOCUMENT_ROOT'] . $this->errorDir;
		}
		return MAD . '/errors/';
	}

	function errorFile($code = 0) {
		$dir = $this->errorDir();
		$file = $dir . $code . '.html';
		if( ! is_file($file) ) {
			$file = $dir . '0.html';
		}
		if( ! is_file($file) ) {
			$file = MAD . '/errors/0.html';
		}

		return $file;
	}

	function errorLayout() {
		if( is_file($_SERVER['DOCUMENT_ROOT'] . $this->errorLayout) ) {
			return new MadView($_SERVER['DOCUMENT_ROOT'] . $this->errorLayout);
		}
		return new MadView(MAD . 'errors/layout.html');
	}

	public static function saveConfig($config, $path = '', $fileName='config.json') {
		$contents = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		return file_put_contents($path.$fileName, $contents);
	}

}
