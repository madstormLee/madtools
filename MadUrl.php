<?php
namespace mad\tools;

class MadUrl {
	private $url = '';
	private $headers = [];
	private $options = [];

	private $method = 'get';
	private $cookie = true;
	private $multipart = false;

	function __construct($url) {
		$server = (object) $_SERVER;
		if($url[0] === '/') {
			$url = "$server->REQUEST_SCHEME://$server->SERVER_NAME" . $url;
		}
		$this->url = $url;
		$this->header("Accept-language: " . $server->HTTP_ACCEPT_LANGUAGE);
	}

	static function post($url, $params, $multipart=false) {
		$url = new self($url);
		$url->method('post');
		$url->multipart($multipart);
		return $url->send($params);
	}

	static function get($url, $params = []) {
		$url = new self($url);
		return $url->send($params);
	}

	function method($value='') {
		if( empty($value) ) {
			return $this->method;
		}
		$this->method = $value;
		return $this;
	}

	function multipart($value='') {
		if( $value === '' ) {
			return $this->multipart;
		}
		$this->multipart = $value;
		return $this;
	}

	function header($value) {
		$this->headers[] = $value;
	}

	function cookie($status = true) {
		$this->cookie = $status;
	}

	function send($params) {
		$server = (object) $_SERVER;
		$this->options['method'] = strToUpper($this->method);
		if($this->cookie) {
			$this->header("Cookie: " . $server->HTTP_COOKIE);
		}

		if( $this->multipart ) {
			$this->header('Content-Type: multipart/form-data; boundary='.$this->boundary());
		} else {
			$this->header('Content-Type: application/x-www-form-urlencoded');
		}

		$this->setQuery($params);

		$this->options['header'] = implode("\r\n", $this->headers);
		$rv = $this->getContents();
		if( $rv[0] === '{' ) {
			return json_decode($rv);
		}
		return $rv; 
	}

	function setQuery($params) {
		if( $this->method == 'post' ) {
			$this->options['content'] = $this->getQuery($params);
		}
		if( $this->method == 'get' ) {
			$this->url .= '?' . $this->getQuery($params);
		}
	}

	function getQuery($params) {
		if( $this->multipart ) {
			return $this->multipartQuery($params);
		}
		return http_build_query($params);
	}

	function getContents() {
		session_write_close();
		$result = file_get_contents($this->url, false, stream_context_create(['http' => $this->options]));
		session_start();
		return $result;
	}

	private function boundary() {
		static $boundary = '';
		if( ! $boundary ) {
			$boundary = '--------------------------'.microtime(true);
		}
		return $boundary;
	}

	function multipartQuery($params) {
		$boundaryRow = "--".$this->boundary();

		$rv = [];
		foreach( $params as $key => $value ) {
			$rv[] = $boundaryRow;
			if( $value[0] == '@' ) {
				$filename = substr($value, 1);
				$rv[] = 'Content-Disposition: form-data; name="'.$key.'"; filename="'.basename($filename).'"';
				$rv[] = "Content-Type: " . mime_content_type($filename);
				$rv[] = '';
				$rv[] = file_get_contents($filename);
			} else {
				$rv[] = 'Content-Disposition: form-data; name="' . $key . '"';
				$rv[] = '';
				$rv[] = $value;
			}
		}
		$rv[] = $boundaryRow;

		$tt = implode("\r\n", $rv) . '--' . "\r\n";
		return $tt;
	}
}
