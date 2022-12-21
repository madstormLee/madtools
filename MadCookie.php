<?php
namespace mad\tools;

class MadCookie {
	function __construct() {}

	function set($key, $value='', $time=0, $path='/', $domain='') {
		if ( setcookie( $key, $value, $time, $path) ) {
			$_COOKIE[$key] = $value;
		}
		return $this;
	}

	function get( $key ) {
		if ( isset($_COOKIE[$key]) ) {
			return $_COOKIE[$key];
		}
		return false;
	}

	function __set($key, $value) {
		$this->set($key, $value);
	}

	function __get($key) {
		return $this->get($key);
	}

	function __unset($key) {
		if ( isset($_COOKIE[$key]) ) {
			unset($_COOKIE[$key]);
			setcookie( $key, '', 1, $this->path );
			return $result;
		}
	}
}
