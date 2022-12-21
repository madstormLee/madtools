<?php
namespace mad\tools;

class MadCurl extends MadData {
	protected $data = array();
	protected $method = 'post';
	protected $url = '';

	function __construct( $url = '' ) {
		$this->setUrl( $url );
	}
	function setUrl( $url ) {
		if ( ! empty( $url ) ) {
			$this->url= $url;
		}
		return $this;
	}
	function getUrl() {
		return $this->url;
	}
	function upload() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_POST, true);
		$post = $this->getArray();
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
		$response = curl_exec($ch);
		curl_close( $ch );
	}
	function get() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_URL, $this->url);
		$response = curl_exec($ch);
		curl_close( $ch );
		return $response;
	}
	function getJson() {
		return new MadData( json_decode($this->get()) );
	}
	function post() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_POST, true);
		// same as <input type="file" name="file_box">
		$post = $this->getArray();
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
		$response = curl_exec($ch);
		curl_close( $ch );
		return $response;
	}
}
